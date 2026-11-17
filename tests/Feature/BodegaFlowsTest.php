<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
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

    public function test_client_form_normalizes_peruvian_mobile_numbers_for_whatsapp(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('admin.clients.store'), [
            'name' => 'Ana Paz',
            'client_type' => 'person',
            'document_type' => 'dni',
            'document_number' => '12345678',
            'phone' => '912345678',
            'email' => 'ana@example.test',
            'preferred_contact' => 'whatsapp',
            'status' => 'active',
            'notes' => '',
        ])->assertRedirect(route('admin.clients.index'));

        $this->assertDatabaseHas('clients', [
            'name' => 'Ana Paz',
            'phone' => '51912345678',
        ]);

        $this->from(route('admin.clients.create'))
            ->post(route('admin.clients.store'), [
                'name' => 'Número inválido',
                'client_type' => 'person',
                'document_type' => 'dni',
                'document_number' => '12345678',
                'phone' => '12345',
                'preferred_contact' => 'whatsapp',
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.clients.create'))
            ->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('clients', 1);
    }

    public function test_professional_client_fields_are_saved_and_email_contact_requires_email(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('admin.clients.store'), [
            'name' => 'Restaurante El Mirador',
            'client_type' => 'business',
            'document_type' => 'ruc',
            'document_number' => '20123456789',
            'contact_name' => 'Jorge Navarro',
            'phone' => '51988224466',
            'email' => 'compras@example.test',
            'address' => 'Av. Los Olivos 120',
            'district' => 'Miraflores',
            'city' => 'Lima',
            'preferred_contact' => 'email',
            'source' => 'referral',
            'status' => 'active',
            'notes' => 'Pedido mayorista.',
        ])->assertRedirect(route('admin.clients.index'));

        $this->assertDatabaseHas('clients', [
            'name' => 'Restaurante El Mirador',
            'client_type' => 'business',
            'document_type' => 'ruc',
            'document_number' => '20123456789',
            'contact_name' => 'Jorge Navarro',
            'district' => 'Miraflores',
            'city' => 'Lima',
            'preferred_contact' => 'email',
            'source' => 'referral',
        ]);

        $this->from(route('admin.clients.create'))
            ->post(route('admin.clients.store'), [
                'name' => 'Cliente sin correo',
                'client_type' => 'person',
                'phone' => '51912345678',
                'preferred_contact' => 'email',
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.clients.create'))
            ->assertSessionHasErrors('email');
    }

    public function test_client_form_rejects_non_numeric_phone_and_invalid_dni_before_saving(): void
    {
        $this->actingAs(User::factory()->create());

        $clientData = [
            'name' => 'Cliente de prueba',
            'client_type' => 'person',
            'phone' => '91234A678',
            'preferred_contact' => 'whatsapp',
            'status' => 'active',
        ];

        $this->from(route('admin.clients.create'))
            ->post(route('admin.clients.store'), $clientData)
            ->assertRedirect(route('admin.clients.create'))
            ->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('clients', 0);

        $clientData['phone'] = '912345678';
        $clientData['document_type'] = 'dni';
        $clientData['document_number'] = '1234A678';

        $this->from(route('admin.clients.create'))
            ->post(route('admin.clients.store'), $clientData)
            ->assertRedirect(route('admin.clients.create'))
            ->assertSessionHasErrors('document_number');
        $this->assertDatabaseCount('clients', 0);

        $clientData['document_type'] = 'passport';
        $clientData['document_number'] = 'A12B345';

        $this->post(route('admin.clients.store'), $clientData)
            ->assertRedirect(route('admin.clients.index'));
        $this->assertDatabaseHas('clients', [
            'name' => 'Cliente de prueba',
            'document_type' => 'passport',
            'document_number' => 'A12B345',
        ]);
    }

    public function test_client_form_marks_phone_as_numeric_and_validates_format_in_browser(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.clients.create'))
            ->assertOk()
            ->assertSee('inputmode="numeric"', false)
            ->assertSee('data-numeric-only="true"', false)
            ->assertSee('pattern="(9[0-9]{8}|[1-9][0-9]{9,14})"', false)
            ->assertSee('id="client-document-type" name="document_type" required', false)
            ->assertSee('id="client-document-number"', false)
            ->assertSee('· obligatorio', false)
            ->assertSee('updateDocumentValidation()', false);
    }

    public function test_client_form_separates_person_and_business_fields_and_document_types(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.clients.create'))
            ->assertOk()
            ->assertSee('data-client-fields="business"', false)
            ->assertSee('data-document-for="person"', false)
            ->assertSee('data-document-for="business"', false)
            ->assertSee('Razón social', false)
            ->assertSee('updateClientTypeFields()', false);
    }

    public function test_client_document_type_must_match_person_or_business(): void
    {
        $this->actingAs(User::factory()->create());

        $clientData = [
            'name' => 'Cliente de prueba',
            'client_type' => 'business',
            'document_type' => 'dni',
            'document_number' => '12345678',
            'contact_name' => 'Contacto de prueba',
            'phone' => '912345678',
            'preferred_contact' => 'whatsapp',
            'status' => 'active',
        ];

        $withoutDocument = $clientData;
        unset($withoutDocument['document_type'], $withoutDocument['document_number']);

        $this->from(route('admin.clients.create'))
            ->post(route('admin.clients.store'), $withoutDocument)
            ->assertRedirect(route('admin.clients.create'))
            ->assertSessionHasErrors(['document_type', 'document_number']);
        $this->assertDatabaseCount('clients', 0);

        $this->from(route('admin.clients.create'))
            ->post(route('admin.clients.store'), $clientData)
            ->assertRedirect(route('admin.clients.create'))
            ->assertSessionHasErrors('document_type');
        $this->assertDatabaseCount('clients', 0);

        $clientData['client_type'] = 'person';
        $clientData['document_type'] = 'ruc';
        $clientData['document_number'] = '20123456789';

        $this->from(route('admin.clients.create'))
            ->post(route('admin.clients.store'), $clientData)
            ->assertRedirect(route('admin.clients.create'))
            ->assertSessionHasErrors('document_type');
        $this->assertDatabaseCount('clients', 0);

        $clientData['document_type'] = 'dni';
        $clientData['document_number'] = '1234567';

        $this->from(route('admin.clients.create'))
            ->post(route('admin.clients.store'), $clientData)
            ->assertRedirect(route('admin.clients.create'))
            ->assertSessionHasErrors('document_number');
        $this->assertDatabaseCount('clients', 0);

        $clientData['document_number'] = '12345678';

        $this->post(route('admin.clients.store'), $clientData)
            ->assertRedirect(route('admin.clients.index'));
        $this->assertDatabaseHas('clients', [
            'name' => 'Cliente de prueba',
            'client_type' => 'person',
            'document_type' => 'dni',
            'contact_name' => null,
        ]);

        $clientData['name'] = 'Pasaporte inválido';
        $clientData['document_type'] = 'passport';
        $clientData['document_number'] = 'A12-345';

        $this->from(route('admin.clients.create'))
            ->post(route('admin.clients.store'), $clientData)
            ->assertRedirect(route('admin.clients.create'))
            ->assertSessionHasErrors('document_number');
    }

    public function test_client_profile_shows_consumed_products_and_prefilled_whatsapp_messages(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::query()->create([
            'name' => 'Ana Paz',
            'phone' => '51912345678',
            'document_type' => 'dni',
            'document_number' => '12345678',
            'contact_name' => 'Ana Paz',
            'district' => 'Miraflores',
            'city' => 'Lima',
            'status' => 'active',
        ]);
        $product = Product::query()->create([
            'name' => 'Arroz',
            'category' => 'Abarrotes',
            'unit' => 'kg',
            'cost' => 3,
            'selling_price' => 4,
            'stock' => 10,
            'low_stock_at' => 2,
            'active' => true,
        ]);
        Sale::query()->create([
            'reference' => 'NV-CLIENT-1',
            'client_id' => $client->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 4,
            'unit_cost' => 3,
            'total' => 8,
            'sold_at' => now()->toDateString(),
            'status' => 'paid',
        ]);

        $this->get(route('admin.clients.show', $client))
            ->assertOk()
            ->assertSee('DNI 12345678')
            ->assertSee('Miraflores, Lima')
            ->assertSee('Productos que consume')
            ->assertSee('Arroz')
            ->assertSee('https://wa.me/51912345678?text=Hola%20Ana%20Paz%2C%20te%20escribimos', false)
            ->assertSee(route('admin.sales.index', ['client_id' => $client->id]), false);

        $this->get(route('admin.sales.index', ['client_id' => $client->id]))
            ->assertOk()
            ->assertSee('<option value="'.$client->id.'" selected>', false);
    }

    public function test_deleting_client_preserves_sales_without_the_client_link(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::query()->create([
            'name' => 'Ana Paz',
            'phone' => '51912345678',
            'status' => 'active',
        ]);
        $product = Product::query()->create([
            'name' => 'Arroz',
            'category' => 'Abarrotes',
            'unit' => 'kg',
            'cost' => 3,
            'selling_price' => 4,
            'stock' => 10,
            'low_stock_at' => 2,
            'active' => true,
        ]);
        $sale = Sale::query()->create([
            'reference' => 'NV-CLIENT-2',
            'client_id' => $client->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 4,
            'unit_cost' => 3,
            'total' => 4,
            'sold_at' => now()->toDateString(),
            'status' => 'paid',
        ]);

        $this->delete(route('admin.clients.destroy', $client))
            ->assertRedirect(route('admin.clients.index'));

        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'client_id' => null]);
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
