<?php

namespace App\Http\Controllers;

use App\Models\ContactSubmission;
use App\Models\Product;
use App\Models\SiteSection;
use App\Support\InputGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SiteController extends Controller
{
    private function publicProducts(int $limit = 16): Collection
    {
        return Product::query()
            ->select(['id', 'name', 'category', 'category_id', 'unit', 'selling_price', 'stock', 'low_stock_at'])
            ->where('active', true)
            ->where('is_public', true)
            ->where('stock', '>', 0)
            ->orderBy('category')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    private static function isValidDestination(?string $value): bool
    {
        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return true;
        }

        if (preg_match('/^#[A-Za-z][\w-]*$/', $trimmed) === 1 || preg_match('/^\/(?!\/)[^\s]*$/', $trimmed) === 1) {
            return true;
        }

        if (filter_var($trimmed, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return in_array(strtolower((string) parse_url($trimmed, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    public function home(): View
    {
        $products = $this->publicProducts();

        return view('site.home', [
            'sections' => SiteSection::resolved(),
            'products' => $products,
            'categories' => $products->pluck('category')->filter()->unique()->values(),
        ]);
    }

    public function editor(): View
    {
        $publicProducts = $this->publicProducts();

        return view('admin.site.edit', [
            'sections' => SiteSection::resolved(),
            'defaults' => SiteSection::DEFAULTS,
            'publicProducts' => $publicProducts,
            'publicCategories' => $publicProducts->pluck('category')->filter()->unique()->values(),
            'featuredProduct' => $publicProducts->first(),
            'contactStats' => [
                'total' => ContactSubmission::query()->count(),
                'new' => ContactSubmission::query()->where('status', 'new')->count(),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(
            [
                'sections' => ['required', 'array'],
                'sections.*.title' => ['required', 'string', 'max:160'],
                'sections.*.content' => ['nullable', 'string', 'max:4000'],
                'sections.*.cta_label' => ['nullable', 'string', 'max:80'],
                'sections.*.cta_url' => [
                    'nullable',
                    'string',
                    'max:255',
                    function (string $attribute, mixed $value, \Closure $fail): void {
                        if (! self::isValidDestination(is_string($value) ? $value : null)) {
                            $fail('El enlace del botón debe ser una URL válida (https://...) o una sección del sitio (#productos, #contacto).');
                        }
                    },
                ],
                'sections.*.whatsapp' => [
                    'nullable',
                    'string',
                    'max:32',
                    function (string $attribute, mixed $value, \Closure $fail): void {
                        try {
                            InputGuard::normalizePeruPhone(is_string($value) ? $value : null, required: false, field: $attribute);
                        } catch (ValidationException $e) {
                            $fail($e->validator->errors()->first($attribute) ?: 'Ingresa un WhatsApp válido de Perú (+519XXXXXXXX).');
                        }
                    },
                ],
                'sections.*.active' => ['nullable', 'boolean'],
            ],
            [
                'sections.*.title.required' => 'Cada bloque debe conservar un título visible.',
                'sections.*.title.max' => 'El título no puede superar los 160 caracteres.',
                'sections.*.content.max' => 'El contenido no puede superar los 4000 caracteres.',
                'sections.*.cta_label.max' => 'El texto del botón no puede superar los 80 caracteres.',
            ]
        );

        DB::transaction(function () use ($data): void {
            foreach ($data['sections'] as $key => $section) {
                Validator::make(['section' => $key], ['section' => ['required', 'in:home,banner,about,contact,faq']])->validate();

                $ctaLabel = isset($section['cta_label']) ? trim((string) $section['cta_label']) : '';
                $ctaUrl = isset($section['cta_url']) ? trim((string) $section['cta_url']) : '';
                $rawContent = isset($section['content']) ? trim((string) $section['content']) : '';
                $rawWhatsapp = trim((string) ($section['whatsapp'] ?? ''));
                $whatsappDigits = $key === 'contact'
                    ? InputGuard::normalizePeruPhone($rawWhatsapp, required: false, field: 'sections.contact.whatsapp')
                    : null;

                SiteSection::query()->updateOrCreate(
                    ['section' => $key],
                    [
                        'title' => trim((string) $section['title']),
                        'content' => $rawContent !== '' ? $rawContent : null,
                        'cta_label' => in_array($key, ['home', 'banner', 'contact'], true) && $ctaLabel !== '' ? $ctaLabel : null,
                        'cta_url' => in_array($key, ['home', 'banner'], true) && $ctaUrl !== '' ? $ctaUrl : null,
                        'whatsapp' => $whatsappDigits,
                        'active' => (bool) ($section['active'] ?? false),
                        'sort_order' => SiteSection::DEFAULTS[$key]['sort_order'],
                    ]
                );
            }
        });

        return back()->with('success', 'La página pública quedó actualizada.');
    }

    public function contact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $data['name'] = InputGuard::normalizePersonName($data['name'], 'name');
        $data['message'] = InputGuard::cleanText($data['message']);
        $data['phone'] = (string) InputGuard::normalizePeruPhone($data['phone'], required: true, field: 'phone');

        ContactSubmission::query()->create($data);
        $whatsapp = SiteSection::query()->where('section', 'contact')->value('whatsapp');

        if ($whatsapp) {
            $text = "Hola, soy {$data['name']}. {$data['message']}";

            return redirect()->away('https://wa.me/'.preg_replace('/\D+/', '', $whatsapp).'?text='.urlencode($text));
        }

        return redirect()->route('home')->with('contact_sent', 'Recibimos tu consulta. Pronto nos pondremos en contacto contigo.');
    }
}
