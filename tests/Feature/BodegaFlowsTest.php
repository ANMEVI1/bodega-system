<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BodegaFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_site_loads_and_first_admin_can_be_created_once(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('Bodega Los Olivos');

        $this->post(route('setup.store'), [
            'name' => 'Admin Bodega',
            'email' => 'admin@example.test',
            'password' => 'BodegaSegura2026!',
            'password_confirmation' => 'BodegaSegura2026!',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated();
        $this->get(route('setup'))->assertRedirect(route('login'));
    }

    public function test_admin_dashboard_requires_authentication(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_password_recovery_sends_link_without_revealing_account_existence(): void
    {
        Notification::fake();
        $admin = User::factory()->create();

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => $admin->email])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', 'Si el correo está asociado a una cuenta administradora, recibirás un enlace de recuperación.');
        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'unknown@example.test'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', 'Si el correo está asociado a una cuenta administradora, recibirás un enlace de recuperación.');

        Notification::assertSentTo($admin, ResetPassword::class);
    }

    public function test_admin_sections_render_for_an_authenticated_user(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.clients.index'))->assertOk();
        $this->get(route('admin.products.index'))->assertOk();
        $this->get(route('admin.suppliers.index'))->assertOk();
        $this->get(route('admin.sales.index'))->assertOk();
        $this->get(route('admin.contacts.index'))->assertOk();
        $this->get(route('admin.site.edit'))->assertOk();
        $this->get(route('admin.clients.create'))->assertOk();
        $this->get(route('admin.products.create'))->assertOk();
        $this->get(route('admin.categories.index'))->assertOk();
        $this->get(route('admin.inventory.index'))->assertOk();
        $this->get(route('admin.suppliers.create'))->assertOk();
    }

    public function test_sale_is_recorded_and_stock_is_decremented_atomically(): void
    {
        $this->actingAs(User::factory()->create());
        $product = Product::query()->create([
            'name' => 'Arroz',
            'category' => 'Abarrotes',
            'unit' => 'kg',
            'cost' => 3,
            'unit_expense' => 0.5,
            'selling_price' => 4,
            'stock' => 10,
            'low_stock_at' => 2,
            'active' => true,
        ]);

        $this->post(route('admin.sales.store'), [
            'product_id' => $product->id,
            'quantity' => 3,
            'sold_at' => now()->toDateString(),
            'status' => 'paid',
        ])->assertRedirect(route('admin.sales.index'));

        $this->assertDatabaseHas('sales', ['product_id' => $product->id, 'quantity' => 3, 'total' => 12, 'unit_cost' => 3.5]);
        $this->assertSame('7.00', $product->fresh()->stock);
    }

    public function test_product_price_recommendation_includes_recorded_unit_expenses(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('admin.products.store'), [
            'name' => 'Avena',
            'category' => 'Abarrotes',
            'unit' => 'bolsa',
            'cost' => 3,
            'unit_expense' => 0.5,
            'margin' => 25,
            'stock' => 10,
            'low_stock_at' => 2,
            'active' => 1,
        ])->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', ['name' => 'Avena', 'unit_expense' => 0.5, 'selling_price' => 4.67]);
    }

    public function test_sale_cannot_exceed_available_stock(): void
    {
        $this->actingAs(User::factory()->create());
        $product = Product::query()->create([
            'name' => 'Aceite',
            'category' => 'Abarrotes',
            'unit' => 'unidad',
            'cost' => 5,
            'selling_price' => 7,
            'stock' => 2,
            'low_stock_at' => 1,
            'active' => true,
        ]);

        $this->post(route('admin.sales.store'), [
            'product_id' => $product->id,
            'quantity' => 3,
            'sold_at' => now()->toDateString(),
            'status' => 'paid',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('sales', 0);
        $this->assertSame('2.00', $product->fresh()->stock);
    }

    public function test_public_contact_is_saved_and_returns_confirmation(): void
    {
        $this->post(route('contact.send'), [
            'name' => 'Ana Paz',
            'phone' => '51912345678',
            'message' => '¿Tienen arroz?',
        ])->assertRedirect(route('home'))->assertSessionHas('contact_sent');

        $this->assertDatabaseHas('contact_submissions', ['name' => 'Ana Paz', 'status' => 'new']);
    }

    public function test_assistant_can_create_a_product_from_a_valid_message(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson(route('admin.chat'), [
            'message' => 'Producto Avena | Precio: 4.50 | Costo: 3.20 | Stock: 12',
        ])->assertOk()->assertJsonPath('reply', 'Producto Avena guardado en el inventario.');

        $this->assertDatabaseHas('products', ['name' => 'Avena', 'selling_price' => 4.5, 'stock' => 12]);
    }

    public function test_product_export_is_excel_compatible_and_neutralizes_formulas(): void
    {
        $this->actingAs(User::factory()->create());
        Product::query()->create([
            'name' => '=SUM(1,1)',
            'category' => 'Abarrotes',
            'unit' => 'unidad',
            'cost' => 1,
            'selling_price' => 2,
            'stock' => 4,
            'low_stock_at' => 1,
            'active' => true,
        ]);

        $response = $this->get(route('admin.export', ['type' => 'products']))
            ->assertDownload('products-'.now()->format('Y-m-d').'.csv');

        $this->assertStringContainsString("'=SUM(1,1)", $response->streamedContent());
    }

    public function test_demo_seeded_public_and_admin_views_render_cleanly(): void
    {
        $this->seed(DemoSeeder::class);

        $this->get(route('home'))->assertOk()->assertSee('Arroz Extra Costeño 5 kg');

        $this->actingAs(User::factory()->create());

        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Últimos seis meses');
        $this->get(route('admin.clients.index'))->assertOk()->assertSee('María Elena Quispe');
        $this->get(route('admin.products.index'))->assertOk()->assertSee('Arroz Extra Costeño 5 kg');
        $this->get(route('admin.suppliers.index'))->assertOk()->assertSee('Distribuidora Santa Clara');
        $this->get(route('admin.sales.index'))->assertOk()->assertSee('NV-DEMO-16');
        $this->get(route('admin.contacts.index'))->assertOk()->assertSee('Valeria Torres');
        $this->get(route('admin.site.edit'))->assertOk()->assertSee('Guardar cambios del sitio');
    }
}
