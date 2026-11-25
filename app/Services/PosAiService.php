<?php

namespace App\Services;

use App\Exceptions\PosAiException;
use App\Models\Product;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class PosAiService
{
    private const MAX_PRODUCTOS_PROMPT = 80;

    private const UMBRAL_BUSQUEDA_CATALOGO = 25;

    /**
     * @var array<int, string>
     */
    private const METODOS_PAGO = [
        'EFECTIVO',
        'YAPE',
        'PLIN',
        'TRANSFERENCIA',
        'TARJETA',
        'FIADO',
    ];

    /**
     * @var array<string, array<int, string>>
     */
    private const SINONIMOS_PRODUCTO = [
        'chela' => ['cerveza', 'pilsen', 'cristal', 'cusquena', 'heineken', 'corona'],
        'birra' => ['cerveza', 'pilsen', 'cristal', 'cusquena'],
        'gaseosa' => ['inca kola', 'coca cola', 'pepsi', 'sprite', 'fanta', 'guarana', 'soda', 'cola'],
        'soda' => ['gaseosa', 'inca kola', 'coca cola', 'sprite', 'fanta', 'galleta'],
    ];

    private const SYSTEM_PROMPT_POS_CART = <<<'PROMPT'
Eres NegoIA, el asistente de punto de venta (POS) de una bodega peruana.
Tu tarea es interpretar el pedido del cajero o cliente y devolver ÚNICAMENTE un objeto JSON válido (sin markdown, sin comentarios y sin texto adicional) para llenar el carrito de ventas.

Catálogo disponible (formato: [id | nombre | precio | stock unidad]):
{CATALOGO}

Reglas estrictas:
1. Usa exclusivamente productos del catálogo proporcionado. Si un producto no existe en el catálogo, agrégalo a "noEncontrados" usando el término mencionado.
2. En "items", cada elemento debe tener:
   - "productoId": entero exacto del catálogo.
   - "nombre": nombre detectado o del producto.
   - "cantidad": número positivo (> 0). Si no se indica cantidad, usa 1. Soporta decimales (ej. 0.5 para medio kilo, 0.25 para un cuarto).
3. Interpreta jerga y pedidos peruanos de bodega (ej. "chela" o "birra" = cerveza, "gaseosa" o "soda" = gaseosa/bebida, "medio kilo" = 0.5, "un cuarto" = 0.25, "docena" = 12, "six pack" = 6).
4. En "metodoPago", devuelve uno de: "EFECTIVO", "YAPE", "PLIN", "TRANSFERENCIA", "TARJETA", "FIADO", o null si no se menciona.
5. En "advertencias", incluye alertas breves solo si hay ambigüedad o stock limitado.
6. En "mensaje", redacta una confirmación corta y natural en español.

Estructura JSON obligatoria:
{
  "items": [
    { "productoId": 1, "nombre": "Nombre del producto", "cantidad": 1 }
  ],
  "noEncontrados": [],
  "metodoPago": null,
  "advertencias": [],
  "mensaje": "Listo, agregué los productos al carrito."
}
PROMPT;

    /**
     * @return array<string, mixed>
     *
     * @throws PosAiException
     */
    public function fillCartFromMessage(string $mensaje): array
    {
        $inicioMs = (int) round(microtime(true) * 1000);
        $mensajeLimpio = trim($mensaje);

        $productos = Product::query()
            ->with('categoryRelation')
            ->where('active', true)
            ->orderBy('name')
            ->get();

        if ($productos->isEmpty()) {
            return [
                'items' => [],
                'noEncontrados' => [],
                'advertencias' => ['No hay productos activos en el catálogo.'],
                'subtotalEstimado' => 0.0,
                'metodoPagoDetectado' => $this->detectarMetodoPago(null, $mensajeLimpio),
                'mensaje' => 'No hay productos activos en el catálogo.',
                'mensaje_voz' => 'No hay productos activos en el catálogo.',
                'requiere_confirmacion' => false,
                'tokensUsados' => 0,
                'tiempoRespuestaMs' => max(1, (int) round(microtime(true) * 1000) - $inicioMs),
            ];
        }

        $productosPrompt = $this->seleccionarProductosParaPrompt($productos, $mensajeLimpio);
        $catalogoTexto = $productosPrompt
            ->map(fn (Product $product): string => $this->formatearLineaCatalogo($product))
            ->implode("\n");

        $systemPrompt = str_replace('{CATALOGO}', $catalogoTexto, self::SYSTEM_PROMPT_POS_CART);

        $respuestaOllama = $this->consultarOllama($systemPrompt, $mensajeLimpio);
        $iaData = $respuestaOllama['data'];
        $tokensUsados = $respuestaOllama['tokens'];

        $productosPorId = $productos->keyBy('id');
        $agrupados = [];
        $noEncontrados = [];
        $advertencias = is_array($iaData['advertencias'] ?? null)
            ? array_values(array_filter(array_map(fn ($a): string => trim((string) $a), $iaData['advertencias'])))
            : [];

        foreach ((array) ($iaData['items'] ?? []) as $rawItem) {
            if (! is_array($rawItem)) {
                continue;
            }

            $rawId = isset($rawItem['productoId']) && is_numeric($rawItem['productoId'])
                ? (int) $rawItem['productoId']
                : null;
            $nombreDetectado = trim((string) ($rawItem['nombre'] ?? $rawItem['nombreProducto'] ?? ''));
            $cantidad = isset($rawItem['cantidad']) && is_numeric($rawItem['cantidad']) && (float) $rawItem['cantidad'] > 0
                ? round((float) $rawItem['cantidad'], 3)
                : 1.0;

            /** @var Product|null $product */
            $product = $rawId !== null ? $productosPorId->get($rawId) : null;

            if ($product === null && $nombreDetectado !== '') {
                $product = $this->buscarProductoEnCatalogo($nombreDetectado, $productos);
            }

            if ($product === null) {
                if ($nombreDetectado !== '') {
                    $noEncontrados[] = $nombreDetectado;
                }

                continue;
            }

            $this->agregarItemAgrupado($agrupados, $product, $cantidad, $nombreDetectado !== '' ? $nombreDetectado : $product->name);
        }

        foreach ((array) ($iaData['noEncontrados'] ?? []) as $rawMissing) {
            $termino = trim((string) $rawMissing);
            if ($termino === '') {
                continue;
            }

            $product = $this->buscarProductoEnCatalogo($termino, $productos);
            if ($product !== null) {
                $cantidad = $this->extraerCantidadDeMensaje($termino, $mensajeLimpio);
                $this->agregarItemAgrupado($agrupados, $product, $cantidad, $termino);

                continue;
            }

            $noEncontrados[] = $termino;
        }

        $items = [];
        $subtotalEstimado = 0.0;

        foreach ($agrupados as $entry) {
            /** @var Product $product */
            $product = $entry['product'];
            $cantidad = round((float) $entry['cantidad'], 3);
            $precioUnitario = round((float) $product->selling_price, 2);
            $stockActual = round((float) $product->stock, 3);
            $stockMinimo = round((float) $product->low_stock_at, 3);
            $subtotal = round($precioUnitario * $cantidad, 2);
            $excedeStock = $cantidad > $stockActual;

            if ($excedeStock) {
                $advertencias[] = sprintf(
                    'Stock insuficiente para %s: solicitado %s, disponible %s.',
                    $product->name,
                    $this->formatearNumero($cantidad),
                    $this->formatearNumero($stockActual)
                );
            }

            $subtotalEstimado += $subtotal;

            $items[] = [
                'productoId' => (int) $product->id,
                'nombreProducto' => (string) $product->name,
                'categoria' => $product->categoryRelation?->name ?: ($product->category ?: 'General'),
                'codigoSku' => $product->sku ?? '',
                'precioUnitario' => $precioUnitario,
                'stockActual' => $stockActual,
                'stockMinimo' => $stockMinimo,
                'unidadMedida' => $product->unit ?: 'unidad',
                'cantidad' => $cantidad,
                'subtotal' => $subtotal,
                'nombreDetectado' => (string) $entry['nombreDetectado'],
                'excedeStock' => $excedeStock,
            ];
        }

        $subtotalEstimado = round($subtotalEstimado, 2);
        $noEncontrados = array_values(array_unique($noEncontrados));
        $advertencias = array_values(array_unique($advertencias));

        $metodoPagoDetectado = $this->detectarMetodoPago(
            isset($iaData['metodoPago']) ? (string) $iaData['metodoPago'] : null,
            $mensajeLimpio
        );

        $mensajeVoz = $this->construirMensajeVoz($items, $noEncontrados, $advertencias, $subtotalEstimado, $metodoPagoDetectado);
        $mensajeTexto = $this->construirMensajeTexto(
            $items,
            $noEncontrados,
            $subtotalEstimado,
            isset($iaData['mensaje']) ? trim((string) $iaData['mensaje']) : ''
        );

        $tiempoRespuestaMs = max(1, (int) round(microtime(true) * 1000) - $inicioMs);

        return [
            'items' => $items,
            'noEncontrados' => $noEncontrados,
            'advertencias' => $advertencias,
            'subtotalEstimado' => $subtotalEstimado,
            'metodoPagoDetectado' => $metodoPagoDetectado,
            'mensaje' => $mensajeTexto,
            'mensaje_voz' => $mensajeVoz,
            'requiere_confirmacion' => count($items) > 0,
            'tokensUsados' => $tokensUsados,
            'tiempoRespuestaMs' => $tiempoRespuestaMs,
        ];
    }

    /**
     * @param  Collection<int, Product>  $productos
     * @return Collection<int, Product>
     */
    private function seleccionarProductosParaPrompt(Collection $productos, string $mensaje): Collection
    {
        if ($productos->count() <= self::MAX_PRODUCTOS_PROMPT) {
            return $productos;
        }

        $tokens = $this->extraerTokensConsulta($this->normalizarTexto($mensaje));

        if ($tokens === []) {
            return $productos->take(self::MAX_PRODUCTOS_PROMPT)->values();
        }

        return $productos
            ->map(function (Product $product) use ($tokens): array {
                $score = $this->calcularPuntajeProducto($product, $tokens, '');

                return [
                    'product' => $product,
                    'score' => $score,
                ];
            })
            ->sort(function (array $a, array $b): int {
                if ($a['score'] !== $b['score']) {
                    return $b['score'] <=> $a['score'];
                }

                return strcmp($a['product']->name, $b['product']->name);
            })
            ->take(self::MAX_PRODUCTOS_PROMPT)
            ->pluck('product')
            ->values();
    }

    private function formatearLineaCatalogo(Product $product): string
    {
        $precio = number_format((float) $product->selling_price, 2, '.', '');
        $stock = $this->formatearNumero((float) $product->stock);
        $unidad = $product->unit ?: 'unidad';

        return "[{$product->id} | {$product->name} | S/ {$precio} | {$stock} {$unidad}]";
    }

    /**
     * @return array{data: array<string, mixed>, tokens: int}
     *
     * @throws PosAiException
     */
    private function consultarOllama(string $systemPrompt, string $mensaje): array
    {
        $baseUrl = rtrim((string) config('services.ollama.base_url', 'http://127.0.0.1:11434'), '/');
        $model = (string) config('services.ollama.model', 'qwen2.5:14b');
        $temperature = (float) config('services.ollama.temperature', 0.1);
        $timeoutMs = (int) config('services.ollama.timeout_ms', 90000);
        $keepAlive = (string) config('services.ollama.keep_alive', '30m');
        $numCtx = (int) config('services.ollama.num_ctx', 4096);
        $timeoutSec = max(1, (int) ceil($timeoutMs / 1000));

        $payload = [
            'model' => $model,
            'stream' => false,
            'format' => 'json',
            'keep_alive' => $keepAlive,
            'options' => [
                'temperature' => $temperature,
                'num_ctx' => $numCtx,
            ],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $mensaje],
            ],
        ];

        $ultimoError = null;

        for ($intento = 0; $intento < 2; $intento++) {
            if ($intento > 0) {
                usleep(250000);
            }

            try {
                $response = Http::timeout($timeoutSec)
                    ->acceptJson()
                    ->post($baseUrl.'/api/chat', $payload);
            } catch (ConnectionException $e) {
                if ($this->esTimeout($e->getMessage())) {
                    throw new PosAiException(
                        'La IA local tardó demasiado en responder. Intenta nuevamente.',
                        504,
                        'IA_TIMEOUT',
                        $e
                    );
                }

                throw new PosAiException(
                    'No se pudo conectar con el servicio local de IA (Ollama).',
                    503,
                    'IA_OLLAMA_NO_DISPONIBLE',
                    $e
                );
            }

            if ($response->failed()) {
                $ultimoError = new PosAiException(
                    'El proveedor local de IA devolvió un error HTTP '.$response->status().'.',
                    502,
                    'IA_ERROR_PROVEEDOR'
                );

                continue;
            }

            $body = $response->json();
            if (! is_array($body)) {
                $ultimoError = new PosAiException(
                    'La respuesta de Ollama no tiene un formato JSON válido.',
                    502,
                    'IA_ERROR_PROVEEDOR'
                );

                continue;
            }

            $rawContent = (string) ($body['message']['content'] ?? '');
            $decoded = $this->decodificarContenidoJson($rawContent);

            if ($decoded === null) {
                $ultimoError = new PosAiException(
                    'No se pudo interpretar la respuesta estructurada de la IA.',
                    502,
                    'IA_ERROR_PROVEEDOR'
                );

                continue;
            }

            $tokens = (int) ($body['prompt_eval_count'] ?? 0) + (int) ($body['eval_count'] ?? 0);

            return [
                'data' => $decoded,
                'tokens' => max(0, $tokens),
            ];
        }

        throw $ultimoError ?? new PosAiException(
            'Error inesperado al consultar la IA local.',
            502,
            'IA_ERROR_PROVEEDOR'
        );
    }

    private function esTimeout(string $message): bool
    {
        return preg_match('/timed?\s*out|curl error 28|operation timed out/i', $message) === 1;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodificarContenidoJson(string $raw): ?array
    {
        $clean = trim($raw);
        if ($clean === '') {
            return null;
        }

        if (preg_match('/^```(?:json)?\s*(.+?)\s*```$/is', $clean, $matches) === 1) {
            $clean = trim($matches[1]);
        }

        $decoded = json_decode($clean, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $clean, $matches) === 1) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $agrupados
     */
    private function agregarItemAgrupado(array &$agrupados, Product $product, float $cantidad, string $nombreDetectado): void
    {
        $id = (int) $product->id;

        if (isset($agrupados[$id])) {
            $agrupados[$id]['cantidad'] = round((float) $agrupados[$id]['cantidad'] + $cantidad, 3);

            return;
        }

        $agrupados[$id] = [
            'product' => $product,
            'cantidad' => $cantidad,
            'nombreDetectado' => $nombreDetectado,
        ];
    }

    public function normalizarTexto(string $texto): string
    {
        $lower = mb_strtolower($texto, 'UTF-8');
        $sinTildes = strtr($lower, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n',
        ]);

        $limpio = preg_replace('/[^a-z0-9\s]/', ' ', $sinTildes) ?? '';

        return trim((string) preg_replace('/\s+/', ' ', $limpio));
    }

    /**
     * @return array<int, string>
     */
    public function variantesToken(string $token): array
    {
        $token = $this->normalizarTexto($token);
        if ($token === '') {
            return [];
        }

        $variantes = [$token];

        if (strlen($token) > 4 && str_ends_with($token, 'es')) {
            $variantes[] = substr($token, 0, -2);
        }

        if (strlen($token) > 3 && str_ends_with($token, 's')) {
            $variantes[] = substr($token, 0, -1);
        }

        foreach (array_values($variantes) as $base) {
            if (isset(self::SINONIMOS_PRODUCTO[$base])) {
                foreach (self::SINONIMOS_PRODUCTO[$base] as $sinonimo) {
                    $variantes[] = $sinonimo;
                }
            }
        }

        return array_values(array_unique(array_filter($variantes)));
    }

    /**
     * @param  Collection<int, Product>  $productos
     */
    public function buscarProductoEnCatalogo(string $consulta, Collection $productos): ?Product
    {
        $consultaNorm = $this->normalizarTexto($consulta);
        if ($consultaNorm === '' || $productos->isEmpty()) {
            return null;
        }

        $tokens = $this->extraerTokensConsulta($consultaNorm);
        if ($tokens === []) {
            $tokens = array_values(array_filter(explode(' ', $consultaNorm)));
        }

        $mejorProducto = null;
        $mejorPuntaje = 0;

        foreach ($productos as $product) {
            $puntaje = $this->calcularPuntajeProducto($product, $tokens, $consultaNorm);

            if ($puntaje >= self::UMBRAL_BUSQUEDA_CATALOGO && $puntaje > $mejorPuntaje) {
                $mejorPuntaje = $puntaje;
                $mejorProducto = $product;
            }
        }

        return $mejorProducto;
    }

    /**
     * @return array<int, string>
     */
    private function extraerTokensConsulta(string $consultaNorm): array
    {
        $ignorar = [
            'de', 'del', 'la', 'las', 'el', 'los', 'un', 'una', 'unos', 'unas',
            'con', 'para', 'por', 'favor', 'dame', 'quiero', 'ponme', 'lleva',
            'pago', 'y', 'o', 'en', 'al', 'me', 'das', 'vende', 'vendeme',
        ];

        $partes = preg_split('/\s+/', $consultaNorm, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter(
            $partes,
            fn (string $part): bool => strlen($part) >= 2 && ! in_array($part, $ignorar, true) && ! is_numeric($part)
        ));
    }

    /**
     * @param  array<int, string>  $tokens
     */
    private function calcularPuntajeProducto(Product $product, array $tokens, string $consultaNorm): int
    {
        $nameNorm = $this->normalizarTexto($product->name);
        $skuNorm = $this->normalizarTexto((string) $product->sku);
        $catNorm = $this->normalizarTexto($product->categoryRelation?->name ?: (string) $product->category);

        if ($consultaNorm !== '' && $nameNorm === $consultaNorm) {
            return 100;
        }

        if ($consultaNorm !== '' && $skuNorm !== '' && $skuNorm === $consultaNorm) {
            return 95;
        }

        $score = 0;

        if ($consultaNorm !== '' && str_contains($nameNorm, $consultaNorm)) {
            $score += 60;
        }

        foreach ($tokens as $token) {
            foreach ($this->variantesToken($token) as $variante) {
                if (str_contains($nameNorm, $variante)) {
                    $score += $variante === $token ? 35 : 30;
                    break;
                }

                if ($skuNorm !== '' && str_contains($skuNorm, $variante)) {
                    $score += 28;
                    break;
                }

                if ($catNorm !== '' && str_contains($catNorm, $variante)) {
                    $score += 18;
                    break;
                }
            }
        }

        return $score;
    }

    private function extraerCantidadDeMensaje(string $termino, string $mensaje): float
    {
        if (preg_match('/^(\d+(?:[.,]\d+)?)\s+/u', trim($termino), $match) === 1) {
            $qty = (float) str_replace(',', '.', $match[1]);
            if ($qty > 0) {
                return round($qty, 3);
            }
        }

        $terminoNorm = $this->normalizarTexto($termino);
        $mensajeNorm = $this->normalizarTexto($mensaje);

        if ($terminoNorm !== '') {
            $palabras = array_map(fn (string $w): string => preg_quote($w, '/'), explode(' ', $terminoNorm));
            $patron = '/\b(\d+(?:[.,]\d+)?)\s+(?:'.implode('|', $palabras).')\b/u';
            if (preg_match($patron, $mensajeNorm, $match) === 1) {
                $qty = (float) str_replace(',', '.', $match[1]);
                if ($qty > 0) {
                    return round($qty, 3);
                }
            }
        }

        return 1.0;
    }

    private function detectarMetodoPago(?string $metodoIa, string $mensaje): ?string
    {
        $candidato = strtoupper(trim((string) $metodoIa));
        if (in_array($candidato, self::METODOS_PAGO, true)) {
            return $candidato;
        }

        $norm = $this->normalizarTexto($mensaje);

        return match (true) {
            preg_match('/\byape\b/', $norm) === 1 => 'YAPE',
            preg_match('/\bplin\b/', $norm) === 1 => 'PLIN',
            preg_match('/\b(fiado|fiar|fia|credito)\b/', $norm) === 1 => 'FIADO',
            preg_match('/\btransferencia\b/', $norm) === 1 => 'TRANSFERENCIA',
            preg_match('/\b(tarjeta|visa|mastercard|pos)\b/', $norm) === 1 => 'TARJETA',
            preg_match('/\b(efectivo|cash|contado)\b/', $norm) === 1 => 'EFECTIVO',
            default => null,
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, string>  $noEncontrados
     * @param  array<int, string>  $advertencias
     */
    private function construirMensajeVoz(
        array $items,
        array $noEncontrados,
        array $advertencias,
        float $subtotalEstimado,
        ?string $metodoPago
    ): string {
        if ($items === []) {
            if ($noEncontrados !== []) {
                return 'No encontré en el catálogo: '.implode(', ', $noEncontrados).'.';
            }

            return 'No pude identificar productos del catálogo en tu pedido.';
        }

        $lista = $this->formatearListaVoz($items);
        $voz = "Agregué {$lista} al carrito. Subtotal estimado: ".$this->montoEnVoz($subtotalEstimado).'.';

        if ($metodoPago !== null) {
            $voz .= ' Método de pago detectado: '.strtolower($metodoPago).'.';
        }

        if ($noEncontrados !== []) {
            $voz .= ' No encontré: '.implode(', ', $noEncontrados).'.';
        }

        if ($advertencias !== []) {
            $voz .= ' Revisa las alertas de stock.';
        }

        return $voz;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, string>  $noEncontrados
     */
    private function construirMensajeTexto(
        array $items,
        array $noEncontrados,
        float $subtotalEstimado,
        string $mensajeIa
    ): string {
        if ($items === []) {
            return $noEncontrados !== []
                ? 'No encontré en el catálogo: '.implode(', ', $noEncontrados).'.'
                : ($mensajeIa !== '' ? $mensajeIa : 'No se encontraron productos coincidentes en el catálogo.');
        }

        $lista = $this->formatearListaVoz($items);
        $base = "Listo, agregué {$lista} (S/ ".number_format($subtotalEstimado, 2).').';

        if ($noEncontrados !== []) {
            $base .= ' No encontrados: '.implode(', ', $noEncontrados).'.';
        }

        return $base;
    }

    public function montoEnVoz(float $monto): string
    {
        $monto = max(0.0, round($monto, 2));
        $entero = (int) floor($monto);
        $centavos = (int) round(($monto - $entero) * 100);

        if ($centavos === 100) {
            $entero++;
            $centavos = 0;
        }

        if ($entero === 0) {
            return $centavos === 1 ? '1 céntimo' : "{$centavos} céntimos";
        }

        $soles = $entero === 1 ? 'sol' : 'soles';

        if ($centavos > 0) {
            $centLabel = $centavos === 1 ? 'céntimo' : 'céntimos';

            return "{$entero} {$soles} con {$centavos} {$centLabel}";
        }

        return "{$entero} {$soles}";
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function formatearListaVoz(array $items): string
    {
        $partes = array_map(
            fn (array $item): string => $this->formatearNumero((float) $item['cantidad']).' '.$item['nombreProducto'],
            $items
        );

        $count = count($partes);
        if ($count === 0) {
            return '';
        }

        if ($count === 1) {
            return $partes[0];
        }

        $ultimo = array_pop($partes);

        return implode(', ', $partes).' y '.$ultimo;
    }

    private function formatearNumero(float $numero): string
    {
        $formateado = number_format($numero, 3, '.', '');

        return rtrim(rtrim($formateado, '0'), '.') ?: '0';
    }
}
