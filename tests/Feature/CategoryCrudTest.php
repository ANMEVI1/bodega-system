<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_index_requires_authentication(): void
    {
        $this->get(route('admin.categories.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_list_categories(): void
    {
        $this->actingAs(User::factory()->create());
        Category::create(['name' => 'Lácteos', 'color' => 'sky', 'active' => true]);

        $this->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('Lácteos');
    }

    public function test_authenticated_user_can_create_a_category(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('admin.categories.store'), [
            'name' => 'Snacks',
            'description' => 'Papas, galletas y piqueos',
            'color' => 'amber',
            'active' => 1,
        ])->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Snacks',
            'slug' => 'snacks',
            'color' => 'amber',
        ]);
    }

    public function test_authenticated_user_can_update_a_category(): void
    {
        $this->actingAs(User::factory()->create());
        $category = Category::create(['name' => 'Bebidas Antiguo', 'color' => 'violet', 'active' => true]);

        $this->put(route('admin.categories.update', $category), [
            'name' => 'Bebidas e Hidratantes',
            'description' => 'Nueva descripción',
            'color' => 'sky',
            'active' => 1,
        ])->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Bebidas e Hidratantes',
            'slug' => 'bebidas-e-hidratantes',
            'color' => 'sky',
        ]);
    }

    public function test_cannot_delete_category_with_associated_products(): void
    {
        $this->actingAs(User::factory()->create());
        $category = Category::create(['name' => 'Abarrotes', 'color' => 'amber', 'active' => true]);
        Product::create([
            'name' => 'Fideo Don Vittorio',
            'category' => 'Abarrotes',
            'category_id' => $category->id,
            'unit' => 'bolsa',
            'cost' => 2.5,
            'selling_price' => 3.5,
            'stock' => 10,
            'low_stock_at' => 3,
            'active' => true,
        ]);

        $this->delete(route('admin.categories.destroy', $category))
            ->assertRedirect()
            ->assertSessionHasErrors('category');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_can_delete_empty_category(): void
    {
        $this->actingAs(User::factory()->create());
        $category = Category::create(['name' => 'Temporal', 'color' => 'rose', 'active' => true]);

        $this->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}

