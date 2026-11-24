<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RibbonTypeSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_renders_transparent_interactive_ribbon_type_at_seam_above_footer(): void
    {
        $response = $this->get(route('home'))->assertOk();
        $html = $response->getContent();

        $ribbonPos = strpos($html, 'data-ribbon-type');
        $footerPos = strpos($html, '<footer');

        $this->assertNotFalse($ribbonPos);
        $this->assertNotFalse($footerPos);
        $this->assertLessThan($footerPos, $ribbonPos);

        $response
            ->assertDontSee('bg-[#0B1109]', false)
            ->assertSee('data-ribbon-canvas', false)
            ->assertSee('data-ribbon-phrases', false)
            ->assertSee('role="button"', false)
            ->assertSee('BODEGA LOS OLIVOS · DESPENSA PERUANA · COSECHA DEL DÍA')
            ->assertSee('PEDIDOS DIRECTOS POR WHATSAPP · ATENCIÓN TODOS LOS DÍAS');
    }
}
