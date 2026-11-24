<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SiteSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_displays_synced_public_catalog_and_featured_product(): void
    {
        $this->actingAs(User::factory()->create());

        Product::query()->create([
            'name' => 'Café Tostado Chanchamayo',
            'category' => 'Bebidas',
            'unit' => 'bolsa',
            'cost' => 14,
            'selling_price' => 22.50,
            'stock' => 12,
            'low_stock_at' => 3,
            'active' => true,
            'is_public' => true,
        ]);

        Product::query()->create([
            'name' => 'Producto Oculto Interno',
            'category' => 'Abarrotes',
            'unit' => 'unidad',
            'cost' => 5,
            'selling_price' => 9,
            'stock' => 10,
            'low_stock_at' => 2,
            'active' => true,
            'is_public' => false,
        ]);

        $this->get(route('admin.site.edit'))
            ->assertOk()
            ->assertSee('Guardar cambios del sitio')
            ->assertSee('Productos públicos (1)')
            ->assertSee('Café Tostado Chanchamayo')
            ->assertDontSee('Producto Oculto Interno');
    }

    public function test_admin_can_update_sections_with_internal_anchors_normalized_whatsapp_and_faq(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('admin.site.edit'))
            ->put(route('admin.site.update'), [
                'sections' => [
                    'home' => [
                        'title' => 'Tu bodega moderna en Miraflores.',
                        'content' => 'Productos frescos todos los días directo a tu mesa.',
                        'cta_label' => 'Ver despensa ahora',
                        'cta_url' => '#productos',
                        'active' => '1',
                    ],
                    'banner' => [
                        'title' => 'Cosecha fresca de estación',
                        'content' => 'Frutas y café recién molido.',
                        'cta_label' => 'Ir a consultas',
                        'cta_url' => '#contacto',
                        'active' => '1',
                    ],
                    'about' => [
                        'title' => 'Tradición familiar renovada',
                        'content' => 'Tres décadas atendiendo a nuestros vecinos.',
                        'active' => '1',
                    ],
                    'faq' => [
                        'title' => 'Dudas comunes',
                        'content' => "¿Hacen delivery al barrio? Sí, dentro de 10 cuadras.\n¿Aceptan Yape y Plin? Sí, sin recargo adicional.",
                        'active' => '1',
                    ],
                    'contact' => [
                        'title' => 'Escríbenos hoy mismo',
                        'content' => 'Resolvemos pedidos al instante.',
                        'cta_label' => 'Solicitar pedido',
                        'whatsapp' => '+51 987 654 321',
                        'active' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.site.edit'))
            ->assertSessionHas('success', 'La página pública quedó actualizada.');

        $this->assertSame('51987654321', SiteSection::query()->where('section', 'contact')->value('whatsapp'));
        $this->assertSame('#productos', SiteSection::query()->where('section', 'home')->value('cta_url'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Tu bodega moderna en Miraflores.')
            ->assertSee('Ver despensa ahora')
            ->assertSee('Cosecha fresca de estación')
            ->assertSee('Ir a consultas')
            ->assertSee('Tradición familiar renovada')
            ->assertSee('¿Hacen delivery al barrio?')
            ->assertSee('Sí, dentro de 10 cuadras.')
            ->assertSee('Solicitar pedido')
            ->assertSee('https://wa.me/51987654321', false);
    }

    public function test_toggling_visibility_dynamically_hides_and_falls_back_sections_on_home(): void
    {
        $this->actingAs(User::factory()->create());

        $this->put(route('admin.site.update'), [
            'sections' => [
                'home' => [
                    'title' => 'Portada Apagada',
                    'content' => 'Contenido oculto',
                    'cta_label' => 'Botón oculto',
                    'cta_url' => '#productos',
                    'active' => '0',
                ],
                'banner' => [
                    'title' => 'Lámina Toma el Protagonismo',
                    'content' => 'Promoción de respaldo activa.',
                    'cta_label' => 'Explorar ofertas',
                    'cta_url' => '#productos',
                    'active' => '1',
                ],
                'about' => [
                    'title' => 'Sección Nosotros Oculta',
                    'content' => 'No debería verse.',
                    'active' => '0',
                ],
                'faq' => [
                    'title' => 'Preguntas Ocultas',
                    'content' => '¿Oculta? No debería verse.',
                    'active' => '0',
                ],
                'contact' => [
                    'title' => 'Contacto Oculto',
                    'content' => 'No debería verse.',
                    'cta_label' => 'Enviar',
                    'whatsapp' => '',
                    'active' => '0',
                ],
            ],
        ])->assertRedirect();

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Portada Apagada')
            ->assertSee('Lámina Toma el Protagonismo')
            ->assertSee('Explorar ofertas')
            ->assertDontSee('Sección Nosotros Oculta')
            ->assertDontSee('Preguntas Ocultas')
            ->assertDontSee('Contacto Oculto');
    }

    public function test_editor_validates_malformed_destinations_and_invalid_whatsapp(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('admin.site.edit'))
            ->put(route('admin.site.update'), [
                'sections' => [
                    'home' => [
                        'title' => 'Portada',
                        'content' => 'Texto',
                        'cta_label' => 'Ir',
                        'cta_url' => 'javascript:alert(1)',
                        'active' => '1',
                    ],
                    'contact' => [
                        'title' => 'Contacto',
                        'content' => 'Texto',
                        'whatsapp' => '123',
                        'active' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.site.edit'))
            ->assertSessionHasErrors(['sections.home.cta_url', 'sections.contact.whatsapp']);
    }
}
