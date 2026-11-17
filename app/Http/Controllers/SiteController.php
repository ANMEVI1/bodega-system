<?php

namespace App\Http\Controllers;

use App\Models\ContactSubmission;
use App\Models\Product;
use App\Models\SiteSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class SiteController extends Controller
{
    private const DEFAULTS = [
        'home' => ['title' => 'Lo que buscas, siempre cerca.', 'content' => 'Tu bodega de confianza: productos de calidad, buenos precios y atención cercana.', 'cta_label' => 'Escríbenos por WhatsApp', 'sort_order' => 1],
        'banner' => ['title' => 'Frescura y buenos precios todos los días', 'content' => 'Abarrotes, bebidas y productos esenciales para tu hogar.', 'cta_label' => 'Ver productos', 'sort_order' => 2],
        'about' => ['title' => 'Cerca de ti, como siempre', 'content' => 'Somos una bodega de barrio que te atiende con una sonrisa y lo que necesitas para cada día.', 'cta_label' => null, 'sort_order' => 3],
        'contact' => ['title' => '¿Qué necesitas hoy?', 'content' => 'Déjanos tu mensaje. Te responderemos por WhatsApp.', 'cta_label' => 'Enviar consulta', 'sort_order' => 4],
        'faq' => ['title' => 'Preguntas frecuentes', 'content' => "¿Dónde estamos? En tu barrio, siempre cerca.\n¿Cuáles son sus horarios? Atendemos todos los días.\n¿Puedo consultar disponibilidad? Sí, escríbenos por WhatsApp.", 'cta_label' => null, 'sort_order' => 5],
    ];

    private function sections()
    {
        $saved = SiteSection::query()->get()->keyBy('section');

        return collect(self::DEFAULTS)->map(fn (array $defaults, string $key) => $saved->get($key) ?? new SiteSection($defaults + ['section' => $key, 'active' => true]));
    }

    public function home(): View
    {
        $products = Product::query()
            ->where('active', true)
            ->where('stock', '>', 0)
            ->orderBy('category')
            ->orderBy('name')
            ->limit(16)
            ->get();

        return view('site.home', [
            'sections' => $this->sections(),
            'products' => $products,
            'categories' => $products->pluck('category')->filter()->unique()->values(),
        ]);
    }

    public function editor(): View
    {
        return view('admin.site.edit', ['sections' => $this->sections()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sections' => ['required', 'array'],
            'sections.*.title' => ['required', 'string', 'max:160'],
            'sections.*.content' => ['nullable', 'string', 'max:4000'],
            'sections.*.cta_label' => ['nullable', 'string', 'max:80'],
            'sections.*.cta_url' => ['nullable', 'url', 'max:255'],
            'sections.*.whatsapp' => ['nullable', 'string', 'max:32'],
            'sections.*.active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($data): void {
            foreach ($data['sections'] as $key => $section) {
                Validator::make(['section' => $key], ['section' => ['required', 'in:home,banner,about,contact,faq']])->validate();
                SiteSection::query()->updateOrCreate(['section' => $key], [...$section, 'active' => (bool) ($section['active'] ?? false)]);
            }
        });

        return back()->with('success', 'La página pública quedó actualizada.');
    }

    public function contact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ]);
        ContactSubmission::query()->create($data);
        $whatsapp = SiteSection::query()->where('section', 'contact')->value('whatsapp');

        if ($whatsapp) {
            $text = "Hola, soy {$data['name']}. {$data['message']}";

            return redirect()->away('https://wa.me/'.preg_replace('/\D+/', '', $whatsapp).'?text='.urlencode($text));
        }

        return redirect()->route('home')->with('contact_sent', 'Recibimos tu consulta. Pronto nos pondremos en contacto contigo.');
    }
}
