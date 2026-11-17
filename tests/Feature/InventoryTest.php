<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_requires_authentication(): void
    {
        $this->get(route('admin.inventory.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_inventory(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        Product::create([
            'name' => 'Arroz Extra',
            'category' => 'Abarrotes',
            'unit' => 'kg',
            'cost' => 3,
            'selling_price' => 4.5,
            'stock' => 25,
            'low_stock_at' => 5,
            'active' => true,
        ]);

        $this->get(route('admin.inventory.index'))
            ->assertOk()
            ->assertSee('Control de Inventario')
            ->assertSee('Arroz Extra')
            ->assertSee('25.00');
    }
}
