<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Client;
use App\Models\Product;
use App\Models\Role;
use App\Models\SiteSection;
use App\Models\Supplier;
use App\Models\User;
use App\Support\InputGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class PhoneValidationAndSqlInjectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_contact_form_renders_locked_peru_prefix_and_numeric_limits(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-peru-phone="required"', false)
            ->assertSee('value="+51"', false)
            ->assertSee('maxlength="12"', false)
            ->assertSee('pattern="\+519[0-9]{8}"', false)
            ->assertSee('inputmode="numeric"', false);
    }

    public function test_public_contact_form_rejects_letters_wrong_length_and_sql_injection_in_phone(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);

        $invalidPhones = [
            '+51',
            '+5198765432',       // 8 local digits (too short)
            '+519876543210',     // 10 local digits (too long)
            '+51887654321',      // does not start with 9
            '+5198765432a',      // letter
            "'+51987654321 OR 1=1--",
            "51912345678'; DROP TABLE users;--",
            'abc123456789',
        ];

        foreach ($invalidPhones as $badPhone) {
            $this->from(route('home'))
                ->post(route('contact.send'), [
                    'name' => 'Cliente Test',
                    'phone' => $badPhone,
                    'message' => 'Consulta de prueba',
                ])
                ->assertRedirect(route('home'))
                ->assertSessionHasErrors('phone');
        }

        $this->assertDatabaseCount('contact_submissions', 0);

        $this->post(route('contact.send'), [
            'name' => 'Cliente Seguro',
            'phone' => '+51987654321',
            'message' => 'Quiero consultar precios.',
        ])->assertRedirect(route('home'))->assertSessionHas('contact_sent');

        $this->assertDatabaseHas('contact_submissions', [
            'name' => 'Cliente Seguro',
            'phone' => '51987654321',
        ]);
    }

    public function test_admin_modules_enforce_peru_phone_format_and_reject_letters_or_sql_payloads(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        Role::syncSystemCatalog();
        $cajeroRole = Role::query()->where('slug', 'cajero')->firstOrFail();

        // Supplier rejects letters / SQL injection / wrong length
        $this->from(route('admin.suppliers.create'))
            ->post(route('admin.suppliers.store'), [
                'name' => 'Proveedor Test',
                'phone' => '+51987654abc',
            ])
            ->assertRedirect(route('admin.suppliers.create'))
            ->assertSessionHasErrors('phone');

        $this->from(route('admin.suppliers.create'))
            ->post(route('admin.suppliers.store'), [
                'name' => 'Proveedor Test',
                'tax_id' => "20123456789' OR '1'='1",
                'phone' => '+51987654321',
            ])
            ->assertRedirect(route('admin.suppliers.create'))
            ->assertSessionHasErrors('tax_id');

        $this->post(route('admin.suppliers.store'), [
            'name' => 'Proveedor Válido',
            'phone' => '+51987654321',
        ])->assertRedirect(route('admin.suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Proveedor Válido',
            'phone' => '51987654321',
        ]);

        // User optional phone accepts bare +51 cleanly as null, rejects letters/SQLi
        $this->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'name' => 'Operador Uno',
                'email' => 'op1@bodega.test',
                'phone' => "+51912345678' OR 1=1",
                'role_id' => $cajeroRole->id,
                'active' => '1',
                'password' => 'ClaveSegura2026!',
                'password_confirmation' => 'ClaveSegura2026!',
            ])
            ->assertRedirect(route('admin.users.create'))
            ->assertSessionHasErrors('phone');

        $this->post(route('admin.users.store'), [
            'name' => 'Operador Uno',
            'email' => 'op1@bodega.test',
            'phone' => '+51',
            'role_id' => $cajeroRole->id,
            'active' => '1',
            'password' => 'ClaveSegura2026!',
            'password_confirmation' => 'ClaveSegura2026!',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'op1@bodega.test',
            'phone' => null,
        ]);

        // Site editor optional WhatsApp accepts bare +51 as null and rejects invalid Peru phone
        $this->from(route('admin.site.edit'))
            ->put(route('admin.site.update'), [
                'sections' => [
                    'contact' => [
                        'title' => 'Contacto',
                        'content' => 'Escríbenos',
                        'whatsapp' => '+5198765432a',
                        'active' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.site.edit'))
            ->assertSessionHasErrors('sections.contact.whatsapp');

        $this->put(route('admin.site.update'), [
            'sections' => [
                'contact' => [
                    'title' => 'Contacto',
                    'content' => 'Escríbenos',
                    'whatsapp' => '+51',
                    'active' => '1',
                ],
            ],
        ])->assertRedirect();

        $this->assertNull(SiteSection::query()->where('section', 'contact')->value('whatsapp'));
    }

    public function test_search_queries_neutralize_sql_injection_and_like_wildcards(): void
    {
        $admin = User::factory()->create(['name' => 'Admin Seguro']);
        User::factory()->withRole('cajero')->create(['name' => 'Valeria Objetivo']);
        $this->actingAs($admin);

        Category::query()->create(['name' => 'Abarrotes', 'active' => true]);
        Product::query()->create([
            'name' => 'Arroz Costeño 5kg',
            'category' => 'Abarrotes',
            'unit' => 'bolsa',
            'cost' => 15,
            'selling_price' => 21,
            'stock' => 20,
            'low_stock_at' => 5,
            'active' => true,
        ]);
        Client::query()->create([
            'name' => 'María Torres',
            'client_type' => 'person',
            'document_type' => 'dni',
            'document_number' => '45678912',
            'phone' => '51987654321',
            'preferred_contact' => 'whatsapp',
            'status' => 'active',
        ]);
        Supplier::query()->create([
            'name' => 'Distribuidora Sur',
            'phone' => '51944332211',
            'status' => 'active',
        ]);

        // Percent wildcard must be escaped so "%" alone does NOT match normal rows
        $this->assertSame('\\%\\_\\\\', InputGuard::escapeLike('%_\\'));

        $payloads = [
            "%' OR '1'='1",
            "'; DROP TABLE products;--",
            '%_%',
        ];

        foreach ($payloads as $payload) {
            $this->get(route('admin.products.index', ['q' => $payload]))
                ->assertOk()
                ->assertDontSee('Arroz Costeño 5kg');

            $this->get(route('admin.categories.index', ['q' => $payload]))
                ->assertOk()
                ->assertDontSee('Abarrotes');

            $this->get(route('admin.clients.index', ['q' => $payload]))
                ->assertOk()
                ->assertDontSee('María Torres');

            $this->get(route('admin.suppliers.index', ['q' => $payload]))
                ->assertOk()
                ->assertDontSee('Distribuidora Sur');

            $this->get(route('admin.inventory.index', ['q' => $payload]))
                ->assertOk()
                ->assertDontSee('Arroz Costeño 5kg');

            $this->get(route('admin.users.index', ['q' => $payload]))
                ->assertOk()
                ->assertDontSee('Valeria Objetivo')
                ->assertSee('No se encontraron usuarios con esos filtros.');
        }

        // Verify tables still exist intact
        $this->assertDatabaseHas('products', ['name' => 'Arroz Costeño 5kg']);
        $this->assertDatabaseHas('users', ['name' => 'Valeria Objetivo']);
    }

    public function test_person_name_fields_in_home_and_users_block_numbers_and_sql_payloads(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-person-name="true"', false);

        // Setup initial admin rejects digits in name
        $this->from(route('setup'))
            ->post(route('setup.store'), [
                'name' => 'Admin 2026',
                'email' => 'admin2026@bodega.test',
                'password' => 'ClaveSegura2026!',
                'password_confirmation' => 'ClaveSegura2026!',
            ])
            ->assertRedirect(route('setup'))
            ->assertSessionHasErrors('name');

        // Public contact form rejects digits and SQL tokens in name
        foreach (['Ana 123', 'Carlos9', "Ana' OR 1=1--", 'Maria; DROP TABLE users;--'] as $badName) {
            $this->from(route('home'))
                ->post(route('contact.send'), [
                    'name' => $badName,
                    'phone' => '+51987654321',
                    'message' => 'Consulta de precios',
                ])
                ->assertRedirect(route('home'))
                ->assertSessionHasErrors('name');
        }

        // Valid Spanish name passes on public web
        $this->post(route('contact.send'), [
            'name' => 'Ana María Peña',
            'phone' => '+51987654321',
            'message' => 'Consulta de precios',
        ])->assertRedirect(route('home'))->assertSessionHas('contact_sent');

        // Users module rejects digits and SQL tokens in name
        $admin = User::factory()->create(['name' => 'Super Admin Bodega']);
        $this->actingAs($admin);
        Role::syncSystemCatalog();
        $cajeroRole = Role::query()->where('slug', 'cajero')->firstOrFail();

        $this->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('data-person-name="true"', false);

        foreach (['Valeria 2', 'Luis01', "Pedro' = 1"] as $badUserName) {
            $this->from(route('admin.users.create'))
                ->post(route('admin.users.store'), [
                    'name' => $badUserName,
                    'email' => 'test@bodega.test',
                    'phone' => '+51987654321',
                    'role_id' => $cajeroRole->id,
                    'active' => '1',
                    'password' => 'ClaveSegura2026!',
                    'password_confirmation' => 'ClaveSegura2026!',
                ])
                ->assertRedirect(route('admin.users.create'))
                ->assertSessionHasErrors('name');
        }

        $this->post(route('admin.users.store'), [
            'name' => 'José Ángel Torres',
            'email' => 'jose.torres@bodega.test',
            'phone' => '+51987654321',
            'role_id' => $cajeroRole->id,
            'active' => '1',
            'password' => 'ClaveSegura2026!',
            'password_confirmation' => 'ClaveSegura2026!',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'jose.torres@bodega.test',
            'name' => 'José Ángel Torres',
        ]);
    }
}
