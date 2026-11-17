<?php

namespace Database\Seeders;

use App\Models\SiteSection;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach ([
            'home' => ['title' => 'Lo que buscas, siempre cerca.', 'content' => 'Tu bodega de confianza: productos de calidad, buenos precios y atención cercana.', 'cta_label' => 'Escríbenos por WhatsApp', 'sort_order' => 1],
            'banner' => ['title' => 'Frescura y buenos precios todos los días', 'content' => 'Abarrotes, bebidas y productos esenciales para tu hogar.', 'cta_label' => 'Ver productos', 'sort_order' => 2],
            'about' => ['title' => 'Cerca de ti, como siempre', 'content' => 'Somos una bodega de barrio que te atiende con una sonrisa y lo que necesitas para cada día.', 'sort_order' => 3],
            'contact' => ['title' => '¿Qué necesitas hoy?', 'content' => 'Déjanos tu mensaje. Te responderemos por WhatsApp.', 'cta_label' => 'Enviar consulta', 'sort_order' => 4],
            'faq' => ['title' => 'Preguntas frecuentes', 'content' => "¿Dónde estamos? En tu barrio, siempre cerca.\n¿Cuáles son sus horarios? Atendemos todos los días.\n¿Puedo consultar disponibilidad? Sí, escríbenos por WhatsApp.", 'sort_order' => 5],
        ] as $section => $content) {
            SiteSection::query()->firstOrCreate(['section' => $section], $content + ['active' => true]);
        }
    }
}
