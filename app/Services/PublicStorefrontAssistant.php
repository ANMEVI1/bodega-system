<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSection;
use Illuminate\Support\Collection;

class PublicStorefrontAssistant
{
    /**
     * Common Spanish filler tokens ignored when matching customer intent keywords.
     *
     * @var array<int, string>
     */
    private const STOPWORDS = [
        'a', 'al', 'algo', 'algun', 'alguna', 'algunas', 'alguno', 'algunos',
        'ante', 'aqui', 'asi', 'aun', 'buen', 'buena', 'buenas', 'buenos',
        'busca', 'buscando', 'busco', 'cada', 'cerca', 'como', 'con', 'cual',
        'cuales', 'cuanta', 'cuantas', 'cuanto', 'cuantos', 'cuesta',
        'cuestan', 'dame', 'de', 'del', 'dos', 'el', 'ella', 'ellos',
        'en', 'entre', 'era', 'eres', 'es', 'esa', 'esas', 'ese', 'eso', 'esos',
        'esta', 'estan', 'estas', 'este', 'esto', 'estos', 'favor', 'gracias',
        'hay', 'hola', 'hoy', 'info', 'informacion', 'la', 'las', 'le', 'les',
        'lo', 'los', 'mas', 'me', 'mi', 'mis', 'muchas', 'mucho', 'muy', 'necesito',
        'nos', 'o', 'para', 'pero', 'por', 'precio', 'precios', 'producto',
        'productos', 'puede', 'pueden', 'puedo', 'que', 'quien', 'quienes',
        'quiero', 'saber', 'se', 'si', 'sin', 'sobre', 'solo', 'son', 'su',
        'sus', 'tal', 'tambien', 'te', 'tendra', 'tendran', 'tengo', 'tiene',
        'tienen', 'tienda', 'tu', 'tus', 'un', 'una', 'unas', 'uno', 'unos',
        'usted', 'ustedes', 'vale', 'valen', 'valor', 'venden', 'ver', 'y', 'ya',
    ];

    /**
     * Build a strictly sanitized public snapshot from editable sections and public catalog.
     *
     * @return array<string, mixed>
     */
    public function buildPublicSnapshot(): array
    {
        $sections = SiteSection::resolved();

        $activeHome = $sections['home']->active ? $sections['home'] : null;
        $activeBanner = $sections['banner']->active ? $sections['banner'] : null;
        $activeAbout = $sections['about']->active ? $sections['about'] : null;
        $activeFaq = $sections['faq']->active ? $sections['faq'] : null;
        $activeContact = $sections['contact']->active ? $sections['contact'] : null;

        $whatsappRaw = $sections['contact']->whatsapp ? (preg_replace('/\D+/', '', (string) $sections['contact']->whatsapp) ?? '') : '';
        $whatsappDigits = strlen($whatsappRaw) >= 8 ? $whatsappRaw : null;

        // Strict column allowlist: never select cost, unit_expense, sku, or supplier/sales relations.
        $allPublicProducts = Product::query()
            ->select(['id', 'name', 'category', 'category_id', 'unit', 'selling_price', 'stock', 'low_stock_at'])
            ->where('active', true)
            ->where('is_public', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $inStockProducts = $allPublicProducts->filter(fn (Product $p) => (float) $p->stock > 0)->values();
        $outOfStockProducts = $allPublicProducts->filter(fn (Product $p) => (float) $p->stock <= 0)->values();

        $activeCategories = Category::query()
            ->select(['id', 'name', 'description'])
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $catalogCategories = $inStockProducts->pluck('category')->filter()->unique()->values();

        return [
            'sections' => [
                'home' => $activeHome,
                'banner' => $activeBanner,
                'about' => $activeAbout,
                'faq' => $activeFaq,
                'contact' => $activeContact,
            ],
            'faqs' => $activeFaq ? SiteSection::parseFaqLines($activeFaq->content) : [],
            'whatsapp' => $whatsappDigits,
            'in_stock_products' => $inStockProducts,
            'out_of_stock_products' => $outOfStockProducts,
            'categories' => $activeCategories,
            'catalog_categories' => $catalogCategories,
            'featured_product' => $inStockProducts->first(),
        ];
    }

    /**
     * Generate a grounded, safe customer response based solely on the bodega's public snapshot.
     *
     * @return array<string, mixed>
     */
    public function respond(string $message): array
    {
        $snapshot = $this->buildPublicSnapshot();
        $clean = trim($message);
        $normalized = $this->normalize($clean);

        if ($this->isPrivateOrInjectionProbe($clean, $normalized)) {
            return [
                'intent' => 'security_blocked',
                'reply' => 'Por seguridad y privacidad, solo brindo información pública de la tienda: catálogo disponible, precios, preguntas frecuentes y canales de atención.',
                'products' => [],
                'actions' => $this->defaultActions($snapshot),
                'suggestions' => $this->dynamicSuggestions($snapshot),
            ];
        }

        $tokens = $this->extractKeywords($normalized);

        if ($this->isGreetingOnly($normalized, $tokens)) {
            return $this->greetingResponse($snapshot);
        }

        if ($this->isFarewell($normalized)) {
            return [
                'intent' => 'farewell',
                'reply' => '¡Con gusto! Cuando necesites consultar algún producto, precio o coordinar un pedido, aquí estaré para ayudarte.',
                'products' => [],
                'actions' => $this->defaultActions($snapshot),
                'suggestions' => $this->dynamicSuggestions($snapshot),
            ];
        }

        // Compare semantic scores across Products, Categories, FAQ, About, and Contact.
        $bestFaq = $this->matchBestFaq($normalized, $tokens, $snapshot['faqs']);
        $matchedCategory = $this->matchCategory($normalized, $tokens, $snapshot);
        $matchedProducts = $this->searchProducts($tokens, $snapshot['in_stock_products']);
        $outOfStockMatch = $this->searchProducts($tokens, $snapshot['out_of_stock_products']);

        if ($this->isBudgetOrRecommendationQuery($normalized) && $snapshot['in_stock_products']->isNotEmpty()) {
            return $this->recommendationResponse($normalized, $snapshot);
        }

        // Direct contact / WhatsApp inquiry
        if ($this->isContactIntent($normalized) && ! $matchedProducts->isNotEmpty() && ($bestFaq['score'] ?? 0) < 2) {
            return $this->contactResponse($snapshot);
        }

        // Direct store identity / about inquiry
        if ($this->isAboutIntent($normalized) && ! $matchedProducts->isNotEmpty()) {
            return $this->aboutResponse($snapshot);
        }

        // Strong FAQ match wins if FAQ keywords outweigh or match product search
        if ($bestFaq !== null && $bestFaq['score'] >= 2 && ($matchedProducts->isEmpty() || $bestFaq['score'] >= $this->topProductScore($matchedProducts))) {
            return $this->faqResponse($bestFaq['item'], $snapshot);
        }

        // General FAQ listing request
        if ($this->isGeneralFaqRequest($normalized) && count($snapshot['faqs']) > 0) {
            return $this->faqOverviewResponse($snapshot);
        }

        // Direct product match(es)
        if ($matchedProducts->isNotEmpty()) {
            return $this->productResponse($matchedProducts->pluck('product'), $snapshot);
        }

        // Category match
        if ($matchedCategory !== null) {
            return $this->categoryResponse($matchedCategory, $snapshot);
        }

        // Catalog overview / general products query
        if ($this->isCatalogOverviewRequest($normalized)) {
            return $this->catalogOverviewResponse($snapshot);
        }

        // Public product currently out of stock
        if ($outOfStockMatch->isNotEmpty()) {
            /** @var Product $outItem */
            $outItem = $outOfStockMatch->first()['product'];

            return [
                'intent' => 'product_out_of_stock',
                'reply' => "Por el momento «{$outItem->name}» se encuentra temporalmente sin stock publicado en tienda. Puedes consultarnos cuándo vuelve a ingresar.",
                'products' => [],
                'actions' => $this->contactActions($snapshot, "Hola, quisiera consultar cuándo vuelve a haber stock de {$outItem->name}."),
                'suggestions' => $this->dynamicSuggestions($snapshot),
            ];
        }

        // Lower-threshold FAQ fallback
        if ($bestFaq !== null && $bestFaq['score'] >= 1) {
            return $this->faqResponse($bestFaq['item'], $snapshot);
        }

        return $this->fallbackResponse($snapshot);
    }

    private function normalize(string $text): string
    {
        $lower = mb_strtolower($text, 'UTF-8');
        $folded = strtr($lower, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n', 'à' => 'a', 'è' => 'e', 'ì' => 'i',
            'ò' => 'o', 'ù' => 'u', 'ä' => 'a', 'ë' => 'e', 'ï' => 'i',
            'ö' => 'o',
        ]);

        return trim((string) preg_replace('/[^a-z0-9\s]/', ' ', $folded));
    }

    /**
     * @return array<int, string>
     */
    private function extractKeywords(string $normalized): array
    {
        $parts = preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_filter(
            $parts,
            fn (string $word): bool => strlen($word) >= 2 && ! in_array($word, self::STOPWORDS, true)
        )));
    }

    private function isPrivateOrInjectionProbe(string $raw, string $normalized): bool
    {
        $rawPattern = '/(ignora|olvida|salta)\s+(todas\s+)?(las\s+)?instrucciones|system\s+prompt|prompt\s+del\s+sistema|jailbreak|revela\s+(tu|el)\s+prompt/iu';
        if (preg_match($rawPattern, $raw) === 1) {
            return true;
        }

        $sensitivePattern = '/\b(ventas?\s+(del\s+mes|totales|diarias|semanales)|cuanto\s+(venden|ganan|facturan)|ganancias?|utilidad(?:es)?|margen(?:es)?|rentabilidad|costo\s+(?:de\s+compra|interno|unitario|real)|precio\s+de\s+costo|gasto\s+unitario|(?:lista|datos|numeros?|telefonos?|correos?|nombres?|cuantos?)\s+(?:de\s+)?clientes|compras?\s+de\s+proveedores?|proveedores?|distribuidores?|facturas?\s+de\s+compra|usuarios?|administrador(?:es)?|contrasenas?|claves?|base\s+de\s+datos|tabla\s+\w+|sql|tokens?|api\s*key)\b/iu';

        return preg_match($sensitivePattern, $normalized) === 1;
    }

    /**
     * @param  array<int, string>  $tokens
     */
    private function isGreetingOnly(string $normalized, array $tokens): bool
    {
        $greetings = ['hola', 'buenas', 'buenos dias', 'buenas tardes', 'buenas noches', 'saludos', 'que tal', 'hey', 'hi', 'empezar'];
        if (in_array($normalized, $greetings, true)) {
            return true;
        }

        return count($tokens) === 1 && in_array($tokens[0], ['hola', 'buenas', 'saludos', 'hey'], true);
    }

    private function isFarewell(string $normalized): bool
    {
        return preg_match('/^(muchas\s+)?gracias(\s+totales)?$|\b(hasta\s+luego|chao|adios|nos\s+vemos)\b/i', $normalized) === 1;
    }

    private function isBudgetOrRecommendationQuery(string $normalized): bool
    {
        return preg_match('/\b(barato|economico|economica|menor\s+precio|mas\s+accesible|destacado|recomiendas|recomendado|sugieres|oferta)\b/i', $normalized) === 1;
    }

    private function isContactIntent(string $normalized): bool
    {
        return preg_match('/\b(whatsapp|wasap|wsp|telefono|numero|celular|llamar|contactar|contacto|escribir|hacer\s+(?:un\s+)?pedido|pedir|reservar|encargar|correo|mensaje)\b/i', $normalized) === 1;
    }

    private function isAboutIntent(string $normalized): bool
    {
        return preg_match('/\b(quienes\s+son|sobre\s+(?:ustedes|nosotros|la\s+bodega)|que\s+es\s+esta\s+bodega|historia|conocerl?os|acerca\s+de)\b/i', $normalized) === 1;
    }

    private function isGeneralFaqRequest(string $normalized): bool
    {
        return preg_match('/\b(preguntas\s+frecuentes|faq|dudas\s+comunes|horarios?|ubicacion|donde\s+(?:estan|quedan)|delivery|entregas?|metodos?\s+de\s+pago|yape|plin)\b/i', $normalized) === 1;
    }

    private function isCatalogOverviewRequest(string $normalized): bool
    {
        return preg_match('/\b(catalogo|productos?|categorias?|venden|ofrecen|disponibles?|stock|despensa|lista\s+de\s+precios|que\s+hay)\b/i', $normalized) === 1;
    }

    /**
     * @param  array<int, string>  $tokens
     * @param  Collection<int, Product>  $products
     * @return Collection<int, array{product: Product, score: int}>
     */
    private function searchProducts(array $tokens, Collection $products): Collection
    {
        if (empty($tokens) || $products->isEmpty()) {
            return collect();
        }

        return $products
            ->map(function (Product $product) use ($tokens): ?array {
                $nameNorm = $this->normalize($product->name);
                $catNorm = $this->normalize((string) $product->category);
                $score = 0;

                foreach ($tokens as $token) {
                    $root = strlen($token) > 4 ? rtrim($token, 's') : $token;
                    if (str_contains($nameNorm, $token) || ($root !== $token && str_contains($nameNorm, $root))) {
                        $score += 3;
                    } elseif (str_contains($catNorm, $token) || ($root !== $token && str_contains($catNorm, $root))) {
                        $score += 1;
                    }
                }

                return $score > 0 ? ['product' => $product, 'score' => $score] : null;
            })
            ->filter()
            ->sortByDesc('score')
            ->values();
    }

    /**
     * @param  Collection<int, array{product: Product, score: int}>  $matches
     */
    private function topProductScore(Collection $matches): int
    {
        $first = $matches->first();

        return is_array($first) ? (int) $first['score'] : 0;
    }

    /**
     * @param  array<int, string>  $tokens
     * @param  array<int, array{q: string, a: string}>  $faqs
     * @return array{item: array{q: string, a: string}, score: int}|null
     */
    private function matchBestFaq(string $normalized, array $tokens, array $faqs): ?array
    {
        if (empty($faqs)) {
            return null;
        }

        $best = null;

        foreach ($faqs as $faq) {
            $qNorm = $this->normalize($faq['q']);
            $aNorm = $this->normalize($faq['a']);
            $score = 0;

            if ($normalized !== '' && (str_contains($qNorm, $normalized) || str_contains($normalized, $qNorm))) {
                $score += 5;
            }

            foreach ($tokens as $token) {
                $root = strlen($token) > 4 ? rtrim($token, 's') : $token;
                if (str_contains($qNorm, $token) || ($root !== $token && str_contains($qNorm, $root))) {
                    $score += 2;
                } elseif (str_contains($aNorm, $token) || ($root !== $token && str_contains($aNorm, $root))) {
                    $score += 1;
                }
            }

            if ($score > 0 && ($best === null || $score > $best['score'])) {
                $best = ['item' => $faq, 'score' => $score];
            }
        }

        return $best;
    }

    /**
     * @param  array<int, string>  $tokens
     * @param  array<string, mixed>  $snapshot
     */
    private function matchCategory(string $normalized, array $tokens, array $snapshot): ?string
    {
        foreach ($snapshot['catalog_categories'] as $categoryName) {
            $catNorm = $this->normalize((string) $categoryName);
            if ($catNorm === '') {
                continue;
            }
            if (str_contains($normalized, $catNorm)) {
                return (string) $categoryName;
            }
            foreach ($tokens as $token) {
                $root = strlen($token) > 4 ? rtrim($token, 's') : $token;
                if ($token === $catNorm || str_contains($catNorm, $root)) {
                    return (string) $categoryName;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function greetingResponse(array $snapshot): array
    {
        $hero = $snapshot['sections']['home'] ?? $snapshot['sections']['banner'];
        $intro = $hero?->content
            ? trim((string) $hero->content)
            : 'Estoy aquí para ayudarte con productos disponibles, precios al día y consultas de atención.';

        $count = $snapshot['in_stock_products']->count();
        $catalogNote = $count > 0
            ? " Tenemos {$count} ".($count === 1 ? 'producto disponible' : 'productos disponibles').' en la vitrina de hoy.'
            : '';

        return [
            'intent' => 'greeting',
            'reply' => "¡Hola! Bienvenido a nuestra tienda. {$intro}{$catalogNote}\n\n¿Qué producto o información estás buscando?",
            'products' => [],
            'actions' => $this->defaultActions($snapshot),
            'suggestions' => $this->dynamicSuggestions($snapshot),
        ];
    }

    /**
     * @param  Collection<int, Product>  $products
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function productResponse(Collection $products, array $snapshot): array
    {
        $top = $products->take(4)->values();
        $first = $top->first();
        $count = $products->count();

        $reply = $count === 1 && $first
            ? "Sí, tenemos «{$first->name}» disponible en {$first->category} a S/ ".number_format((float) $first->selling_price, 2)." por {$first->unit}."
            : "Encontré {$count} ".($count === 1 ? 'opción disponible' : 'opciones disponibles').' en nuestro catálogo público:';

        return [
            'intent' => 'product_match',
            'reply' => $reply,
            'products' => $this->formatProductCards($top),
            'actions' => $this->contactActions(
                $snapshot,
                $first ? "Hola, quisiera consultar por \"{$first->name}\"." : 'Hola, quisiera consultar disponibilidad de productos.'
            ),
            'suggestions' => $this->dynamicSuggestions($snapshot),
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function categoryResponse(string $categoryName, array $snapshot): array
    {
        $items = $snapshot['in_stock_products']
            ->filter(fn (Product $p) => mb_strtolower((string) $p->category) === mb_strtolower($categoryName))
            ->values();

        if ($items->isEmpty()) {
            return $this->fallbackResponse($snapshot);
        }

        /** @var Category|null $catModel */
        $catModel = $snapshot['categories']->first(
            fn (Category $c) => mb_strtolower($c->name) === mb_strtolower($categoryName)
        );
        $desc = $catModel?->description ? " ({$catModel->description})" : '';

        return [
            'intent' => 'category_match',
            'reply' => "En la categoría {$categoryName}{$desc} contamos con {$items->count()} ".($items->count() === 1 ? 'producto disponible' : 'productos disponibles').' hoy:',
            'products' => $this->formatProductCards($items->take(4)),
            'actions' => [
                ['type' => 'scroll', 'label' => 'Ver catálogo completo', 'target' => '#productos'],
            ],
            'suggestions' => $this->dynamicSuggestions($snapshot),
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function recommendationResponse(string $normalized, array $snapshot): array
    {
        $isBudget = str_contains($normalized, 'barat') || str_contains($normalized, 'econom') || str_contains($normalized, 'menor') || str_contains($normalized, 'accesible');

        $selected = $isBudget
            ? $snapshot['in_stock_products']->sortBy('selling_price')->take(3)->values()
            : $snapshot['in_stock_products']->take(3)->values();

        $reply = $isBudget
            ? 'Estas son las opciones más económicas disponibles actualmente en tienda:'
            : 'Estas son algunas recomendaciones destacadas disponibles hoy en nuestra vitrina:';

        return [
            'intent' => 'product_recommendation',
            'reply' => $reply,
            'products' => $this->formatProductCards($selected),
            'actions' => $this->defaultActions($snapshot),
            'suggestions' => $this->dynamicSuggestions($snapshot),
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function catalogOverviewResponse(array $snapshot): array
    {
        /** @var Collection<int, Product> $products */
        $products = $snapshot['in_stock_products'];
        /** @var Collection<int, string> $categories */
        $categories = $snapshot['catalog_categories'];

        if ($products->isEmpty()) {
            return [
                'intent' => 'catalog_empty',
                'reply' => 'Actualmente estamos actualizando el inventario visible de la tienda. Puedes escribirnos directamente para confirmar disponibilidad y precios del día.',
                'products' => [],
                'actions' => $this->contactActions($snapshot, 'Hola, quisiera consultar qué productos tienen disponibles hoy.'),
                'suggestions' => $this->dynamicSuggestions($snapshot),
            ];
        }

        $catSummary = $categories->isNotEmpty()
            ? ' en categorías como '.$categories->take(5)->implode(', ')
            : '';

        return [
            'intent' => 'catalog_overview',
            'reply' => "Hoy tenemos {$products->count()} productos publicados con disponibilidad inmediata{$catSummary}. Aquí te muestro algunos:",
            'products' => $this->formatProductCards($products->take(4)),
            'actions' => [
                ['type' => 'scroll', 'label' => 'Explorar catálogo', 'target' => '#productos'],
            ],
            'suggestions' => $this->dynamicSuggestions($snapshot),
        ];
    }

    /**
     * @param  array{q: string, a: string}  $faq
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function faqResponse(array $faq, array $snapshot): array
    {
        return [
            'intent' => 'faq_match',
            'reply' => "{$faq['q']}\n{$faq['a']}",
            'products' => [],
            'actions' => $this->defaultActions($snapshot),
            'suggestions' => $this->dynamicSuggestions($snapshot),
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function faqOverviewResponse(array $snapshot): array
    {
        $lines = collect($snapshot['faqs'])
            ->take(4)
            ->map(fn (array $item): string => "• {$item['q']} {$item['a']}")
            ->implode("\n");

        return [
            'intent' => 'faq_overview',
            'reply' => "Esto es lo que informamos sobre nuestra atención:\n{$lines}",
            'products' => [],
            'actions' => $this->defaultActions($snapshot),
            'suggestions' => $this->dynamicSuggestions($snapshot),
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function aboutResponse(array $snapshot): array
    {
        $about = $snapshot['sections']['about'];
        $hero = $snapshot['sections']['home'] ?? $snapshot['sections']['banner'];

        if (! $about && ! $hero) {
            return $this->fallbackResponse($snapshot);
        }

        $parts = [];
        if ($about) {
            $parts[] = trim("{$about->title}\n{$about->content}");
        } elseif ($hero) {
            $parts[] = trim("{$hero->title}\n{$hero->content}");
        }

        return [
            'intent' => 'about_match',
            'reply' => implode("\n\n", array_filter($parts)),
            'products' => [],
            'actions' => [
                ['type' => 'scroll', 'label' => 'Ver catálogo', 'target' => '#productos'],
            ],
            'suggestions' => $this->dynamicSuggestions($snapshot),
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function contactResponse(array $snapshot): array
    {
        $contact = $snapshot['sections']['contact'];
        $wa = $snapshot['whatsapp'];

        if ($contact && $wa) {
            $reply = "{$contact->title}\n{$contact->content}\n\nPuedes escribirnos directo a nuestro WhatsApp (+{$wa}) o dejarnos tu consulta en el formulario.";
        } elseif ($contact) {
            $reply = "{$contact->title}\n{$contact->content}\n\nCompleta el formulario de contacto en la web y te responderemos a la brevedad.";
        } elseif ($wa) {
            $reply = "Puedes comunicarte directamente por WhatsApp al +{$wa} para consultar disponibilidad y coordinar tu pedido.";
        } else {
            $reply = 'Puedes comunicarte con la tienda a través de los canales habilitados en la página principal.';
        }

        return [
            'intent' => 'contact_match',
            'reply' => $reply,
            'products' => [],
            'actions' => $this->contactActions($snapshot, 'Hola, quisiera hacer una consulta sobre un pedido.'),
            'suggestions' => $this->dynamicSuggestions($snapshot),
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function fallbackResponse(array $snapshot): array
    {
        $catHint = $snapshot['catalog_categories']->isNotEmpty()
            ? ' productos de '.$snapshot['catalog_categories']->take(3)->implode(', ')
            : ' disponibilidad de productos';

        $faqHint = count($snapshot['faqs']) > 0 ? ', horarios o dudas frecuentes' : '';

        return [
            'intent' => 'fallback',
            'reply' => "No encontré ese detalle exacto en la información publicada de la tienda. Puedo ayudarte con{$catHint}{$faqHint} o conectarte por WhatsApp.",
            'products' => [],
            'actions' => $this->contactActions($snapshot, 'Hola, tengo una consulta que no encontré en la web.'),
            'suggestions' => $this->dynamicSuggestions($snapshot),
        ];
    }

    /**
     * Format strictly safe public product fields for JSON cards.
     *
     * @param  Collection<int, Product>  $products
     * @return array<int, array<string, mixed>>
     */
    private function formatProductCards(Collection $products): array
    {
        return $products->map(function (Product $product): array {
            $isLow = (float) $product->stock <= (float) $product->low_stock_at;

            return [
                'name' => $product->name,
                'category' => $product->category ?: 'Despensa',
                'unit' => $product->unit ?: 'unidad',
                'price_formatted' => 'S/ '.number_format((float) $product->selling_price, 2),
                'status_label' => $isLow ? 'Últimas unidades' : 'En stock',
                'low_stock' => $isLow,
            ];
        })->values()->all();
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<int, array<string, string>>
     */
    private function defaultActions(array $snapshot): array
    {
        $actions = [];
        if ($snapshot['in_stock_products']->isNotEmpty()) {
            $actions[] = ['type' => 'scroll', 'label' => 'Ver catálogo', 'target' => '#productos'];
        }
        if ($snapshot['whatsapp']) {
            $actions[] = ['type' => 'whatsapp', 'label' => 'Abrir WhatsApp', 'url' => 'https://wa.me/'.$snapshot['whatsapp']];
        } elseif ($snapshot['sections']['contact']) {
            $actions[] = ['type' => 'scroll', 'label' => 'Ir a contacto', 'target' => '#contacto'];
        }

        return $actions;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<int, array<string, string>>
     */
    private function contactActions(array $snapshot, string $prefill): array
    {
        $actions = [];
        if ($snapshot['sections']['contact']) {
            $actions[] = [
                'type' => 'prefill_contact',
                'label' => 'Consultar en formulario',
                'target' => '#contacto',
                'prefill' => $prefill,
            ];
        }
        if ($snapshot['whatsapp']) {
            $actions[] = [
                'type' => 'whatsapp',
                'label' => 'Escribir por WhatsApp',
                'url' => 'https://wa.me/'.$snapshot['whatsapp'].'?text='.urlencode($prefill),
            ];
        }

        return $actions;
    }

    /**
     * Dynamically derive starter / follow-up chips from whatever sections and catalog exist for this store.
     *
     * @param  array<string, mixed>  $snapshot
     * @return array<int, string>
     */
    public function dynamicSuggestions(array $snapshot): array
    {
        $suggestions = [];

        if ($snapshot['in_stock_products']->isNotEmpty()) {
            $suggestions[] = '¿Qué productos tienen disponibles?';
        }

        if ($snapshot['featured_product']) {
            $suggestions[] = 'Precio de '.$snapshot['featured_product']->name;
        }

        if (count($snapshot['faqs']) > 0) {
            $suggestions[] = $snapshot['faqs'][0]['q'];
        }

        if ($snapshot['sections']['about']) {
            $suggestions[] = '¿Quiénes son?';
        }

        if ($snapshot['whatsapp'] || $snapshot['sections']['contact']) {
            $suggestions[] = '¿Cómo hago un pedido?';
        }

        return array_slice(array_values(array_unique($suggestions)), 0, 3);
    }
}
