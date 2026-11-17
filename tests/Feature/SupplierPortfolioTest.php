<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\SupplierPurchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SupplierPortfolioTest extends TestCase
{
    use RefreshDatabase;

    public function test_suppliers_portfolio_works_with_zero_products_and_shows_connection_slot(): void
    {
        $this->actingAs(User::factory()->create());

        Supplier::query()->create([
            'name' => 'Distribuidora Andina SAC',
            'tax_id' => '20554433221',
            'contact_name' => 'Elena Quispe',
            'phone' => '+51 987-654-321',
            'email' => 'ventas@andina.pe',
            'address' => 'Av. Los Olivos 450',
            'specialty' => 'Abarrotes',
            'payment_terms' => 'credito_15',
            'status' => 'active',
            'whatsapp_template' => 'cotizacion',
            'notes' => 'Despacho lunes y jueves.',
        ]);

        $this->get(route('admin.suppliers.index'))
            ->assertOk()
            ->assertSee('Distribuidora Andina SAC')
            ->assertSee('20554433221')
            ->assertSee('Crédito 15 días')
            ->assertSee('data-wa-open', false)
            ->assertSee('data-supplier-phone="51987654321"', false)
            ->assertSee('data-supplier-default-tpl="cotizacion"', false)
            ->assertSee('Solicitar cotización actualizada')
            ->assertSee('id="supplier-wa-modal"', false)
            ->assertDontSee('href="https://wa.me/51987654321', false)
            ->assertSee('Conexión con Productos · Diseño de enlace')
            ->assertSee('Sin compras acumuladas por producto todavía');
    }

    public function test_supplier_crud_and_search_filters_work(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('admin.suppliers.store'), [
            'name' => 'Lácteos del Sur',
            'tax_id' => '20998877665',
            'contact_name' => 'Mario Vega',
            'phone' => '51944332211',
            'email' => 'mario@lacteossur.pe',
            'address' => 'Jr. Comercio 120',
            'specialty' => 'Lácteos',
            'payment_terms' => 'contado',
            'status' => 'active',
            'whatsapp_template' => 'despacho',
            'notes' => 'Entrega temprana.',
        ])->assertRedirect(route('admin.suppliers.index'));

        $lacteos = Supplier::query()->where('name', 'Lácteos del Sur')->firstOrFail();

        Supplier::query()->create([
            'name' => 'Bebidas Pacífico',
            'phone' => '51911223344',
            'specialty' => 'Bebidas',
            'payment_terms' => 'credito_30',
            'status' => 'paused',
        ]);

        $this->get(route('admin.suppliers.index', ['q' => '20998877665']))
            ->assertOk()
            ->assertSee('Lácteos del Sur')
            ->assertDontSee('Bebidas Pacífico');

        $this->get(route('admin.suppliers.index', ['specialty' => 'Bebidas', 'status' => 'paused']))
            ->assertOk()
            ->assertSee('Bebidas Pacífico')
            ->assertDontSee('Lácteos del Sur');

        $this->put(route('admin.suppliers.update', $lacteos), [
            'name' => 'Lácteos del Sur SAC',
            'tax_id' => '20998877665',
            'contact_name' => 'Mario Vega',
            'phone' => '51944332211',
            'specialty' => 'Lácteos',
            'payment_terms' => 'credito_30',
            'status' => 'paused',
            'whatsapp_template' => 'pago',
        ])->assertRedirect(route('admin.suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'id' => $lacteos->id,
            'name' => 'Lácteos del Sur SAC',
            'status' => 'paused',
            'payment_terms' => 'credito_30',
            'whatsapp_template' => 'pago',
        ]);

        $this->delete(route('admin.suppliers.destroy', $lacteos))
            ->assertRedirect(route('admin.suppliers.index'));

        $this->assertDatabaseMissing('suppliers', ['id' => $lacteos->id]);
    }

    public function test_supplier_aggregates_accumulated_purchase_stats_per_product_and_protects_history(): void
    {
        $this->actingAs(User::factory()->create());

        $supplier = Supplier::query()->create([
            'name' => 'Mayorista Central',
            'phone' => '51955667788',
            'specialty' => 'Abarrotes',
            'status' => 'active',
        ]);

        $productA = DB::table('products')->insertGetId([
            'name' => 'Producto Externo A',
            'category' => 'Abarrotes',
            'unit' => 'kg',
            'cost' => 10,
            'selling_price' => 14,
            'stock' => 20,
            'low_stock_at' => 5,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productB = DB::table('products')->insertGetId([
            'name' => 'Producto Externo B',
            'category' => 'Abarrotes',
            'unit' => 'unidad',
            'cost' => 5,
            'selling_price' => 7,
            'stock' => 10,
            'low_stock_at' => 2,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        SupplierPurchase::query()->create([
            'supplier_id' => $supplier->id,
            'product_id' => $productA,
            'quantity' => 10,
            'unit_cost' => 10,
            'total' => 100,
            'purchased_at' => now()->subDays(5)->toDateString(),
            'reference' => 'F001-10',
        ]);

        SupplierPurchase::query()->create([
            'supplier_id' => $supplier->id,
            'product_id' => $productA,
            'quantity' => 5,
            'unit_cost' => 12,
            'total' => 60,
            'purchased_at' => now()->subDays(1)->toDateString(),
            'reference' => 'F001-20',
        ]);

        SupplierPurchase::query()->create([
            'supplier_id' => $supplier->id,
            'product_id' => $productB,
            'quantity' => 8,
            'unit_cost' => 5,
            'total' => 40,
            'purchased_at' => now()->toDateString(),
            'reference' => 'F001-30',
        ]);

        $this->get(route('admin.suppliers.index'))
            ->assertOk()
            ->assertSee('S/ 200.00')
            ->assertSee('15 u acumuladas')
            ->assertSee('S/ 160.00')
            ->assertSee('80.0%')
            ->assertSee('8 u acumuladas')
            ->assertSee('S/ 40.00')
            ->assertSee('20.0%');

        $this->delete(route('admin.suppliers.destroy', $supplier))
            ->assertSessionHasErrors('supplier');

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }
}
