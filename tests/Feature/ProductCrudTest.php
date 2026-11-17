<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_newly_added_product_does_not_appear_on_public_home_page(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $category = Category::create(['name' => 'Abarrotes', 'color' => 'amber', 'active' => true]);

        // Create product via admin panel
        $response = $this->post(route('admin.products.store'), [
            'name' => 'Arroz Costeño Privado',
            'sku' => 'ARR-999',
            'category_id' => $category->id,
            'unit' => 'kg',
            'cost' => 3.50,
            'unit_expense' => 0.50,
            'margin' => 25,
            'stock' => 50,
            'low_stock_at' => 10,
            'active' => 1,
            // is_public is omitted / false
        ]);

        $response->assertRedirect(route('admin.products.index'));

        // Verify product exists in database with is_public = false
        $this->assertDatabaseHas('products', [
            'name' => 'Arroz Costeño Privado',
            'is_public' => false,
            'category' => 'Abarrotes',
        ]);

        // Verify it is visible in the admin inventory
        $this->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Arroz Costeño Privado');

        // Logout and verify it does NOT appear on the public home page
        auth()->logout();
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Arroz Costeño Privado');
    }

    public function test_authenticated_user_can_view_product_detail_sheet(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $category = Category::create(['name' => 'Lácteos', 'color' => 'sky', 'active' => true]);
        $product = Product::create([
            'name' => 'Yogurt Fresa 1L',
            'sku' => 'YOG-001',
            'category' => 'Lácteos',
            'category_id' => $category->id,
            'unit' => 'botella',
            'cost' => 4.00,
            'unit_expense' => 0.50,
            'selling_price' => 6.50,
            'stock' => 15,
            'low_stock_at' => 5,
            'active' => true,
            'is_public' => false,
        ]);

        $this->get(route('admin.products.show', $product))
            ->assertOk()
            ->assertSee('Yogurt Fresa 1L')
            ->assertSee('YOG-001')
            ->assertSee('Ficha técnica');
    }

    public function test_filter_by_stock_status_works(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        Product::create([
            'name' => 'Producto Agotado',
            'category' => 'Abarrotes',
            'unit' => 'unidad',
            'cost' => 2,
            'selling_price' => 3,
            'stock' => 0,
            'low_stock_at' => 5,
            'active' => true,
        ]);

        Product::create([
            'name' => 'Producto Abundante',
            'category' => 'Abarrotes',
            'unit' => 'unidad',
            'cost' => 2,
            'selling_price' => 3,
            'stock' => 100,
            'low_stock_at' => 5,
            'active' => true,
        ]);

        // Filter out of stock
        $this->get(route('admin.products.index', ['stock_status' => 'out']))
            ->assertOk()
            ->assertSee('Producto Agotado')
            ->assertDontSee('Producto Abundante');

        // Filter in stock
        $this->get(route('admin.products.index', ['stock_status' => 'in_stock']))
            ->assertOk()
            ->assertSee('Producto Abundante')
            ->assertDontSee('Producto Agotado');
    }
}
