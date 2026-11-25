<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Client;
use App\Models\PosSale;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PosModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_page_renders_root_and_home_includes_pos_link(): void
    {
        $this->withoutVite();

        $this->get(route('site.pos'))
            ->assertOk()
            ->assertSee('id="pos-root"', false)
            ->assertSee('name="csrf-token"', false);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('site.pos'), false);
    }

    public function test_pos_alias_redirects_to_punto_de_venta(): void
    {
        $this->get('/pos')
            ->assertStatus(301)
            ->assertRedirect(route('site.pos'));
    }

    public function test_catalogo_returns_active_products_and_sorted_categories(): void
    {
        $lacteos = Category::query()->create(['name' => 'Lácteos', 'active' => true]);
        $abarrotes = Category::query()->create(['name' => 'Abarrotes', 'active' => true]);

        Product::query()->create([
            'name' => 'Leche Gloria Azul 400g',
            'sku' => 'LAC-001',
            'category' => 'Lácteos',
            'category_id' => $lacteos->id,
            'unit' => 'lata',
            'cost' => 3.50,
            'unit_expense' => 0.20,
            'selling_price' => 4.80,
            'stock' => 4,
            'low_stock_at' => 5,
            'active' => true,
            'is_public' => true,
        ]);

        Product::query()->create([
            'name' => 'Arroz Costeño 1kg',
            'sku' => 'ABA-001',
            'category' => 'Abarrotes',
            'category_id' => $abarrotes->id,
            'unit' => 'bolsa',
            'cost' => 3.80,
            'unit_expense' => 0.10,
            'selling_price' => 5.20,
            'stock' => 20,
            'low_stock_at' => 5,
            'active' => true,
            'is_public' => true,
        ]);

        Product::query()->create([
            'name' => 'Producto Descontinuado',
            'sku' => 'INA-001',
            'category' => 'Bebidas',
            'unit' => 'unidad',
            'cost' => 1.00,
            'unit_expense' => 0,
            'selling_price' => 2.00,
            'stock' => 10,
            'low_stock_at' => 2,
            'active' => false,
            'is_public' => false,
        ]);

        $response = $this->getJson('/api/pos/catalogo');

        $response->assertOk()
            ->assertJsonCount(2, 'data.productos')
            ->assertJsonStructure([
                'data' => [
                    'productos' => [
                        '*' => [
                            'id',
                            'codigoSku',
                            'nombre',
                            'categoria',
                            'precioCompra',
                            'precioVenta',
                            'stockActual',
                            'stockMinimo',
                            'unidadMedida',
                            'bajoStock',
                            'creadoEn',
                            'actualizadoEn',
                        ],
                    ],
                    'categorias',
                ],
            ]);

        $this->assertSame(['Abarrotes', 'Lácteos'], $response->json('data.categorias'));
        $this->assertTrue($response->json('data.productos.1.bajoStock'));
        $this->assertFalse($response->json('data.productos.0.bajoStock'));
        $this->assertIsString($response->json('data.productos.0.codigoSku'));
    }

    public function test_catalogo_normalizes_nullable_sku_to_empty_string(): void
    {
        Product::query()->create([
            'name' => 'Producto Sin SKU',
            'sku' => null,
            'category' => 'General',
            'unit' => 'unidad',
            'cost' => 1.00,
            'unit_expense' => 0,
            'selling_price' => 2.50,
            'stock' => 5,
            'low_stock_at' => 1,
            'active' => true,
            'is_public' => true,
        ]);

        $this->getJson('/api/pos/catalogo')
            ->assertOk()
            ->assertJsonPath('data.productos.0.codigoSku', '');
    }

    public function test_clientes_returns_clients_with_pending_balance_and_meta(): void
    {
        Client::query()->create([
            'name' => 'Carlos Ramos',
            'phone' => '51987654321',
            'document_number' => '45678912',
            'notes' => 'Cliente frecuente',
            'status' => 'active',
            'pending_balance' => 18.50,
        ]);

        $response = $this->getJson('/api/pos/clientes');

        $response->assertOk()
            ->assertJsonPath('meta.page', 1)
            ->assertJsonPath('meta.pageSize', 100)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.nombre', 'Carlos Ramos')
            ->assertJsonPath('data.0.telefono', '51987654321')
            ->assertJsonPath('data.0.documento', '45678912');

        $this->assertEquals(18.50, (float) $response->json('data.0.saldoPendiente'));
    }

    public function test_ai_cart_parses_ollama_json_response(): void
    {
        $gaseosa = Product::query()->create([
            'name' => 'Inca Kola 1.5L',
            'sku' => 'BEB-001',
            'category' => 'Bebidas',
            'unit' => 'botella',
            'cost' => 4.50,
            'unit_expense' => 0,
            'selling_price' => 6.50,
            'stock' => 12,
            'low_stock_at' => 3,
            'active' => true,
            'is_public' => true,
        ]);

        Http::fake([
            'http://127.0.0.1:11434/api/chat' => Http::response([
                'message' => [
                    'content' => json_encode([
                        'items' => [
                            [
                                'productoId' => $gaseosa->id,
                                'nombre' => 'Inca Kola 1.5L',
                                'cantidad' => 2,
                            ],
                        ],
                        'noEncontrados' => [],
                        'metodoPago' => 'YAPE',
                        'advertencias' => [],
                        'mensaje' => 'Agregué 2 Inca Kola 1.5L al carrito.',
                    ], JSON_THROW_ON_ERROR),
                ],
                'prompt_eval_count' => 120,
                'eval_count' => 45,
            ], 200),
        ]);

        $response = $this->postJson('/api/pos/ai-cart', [
            'mensaje' => 'Dame 2 Inca Kola y pago con yape',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.items.0.productoId', $gaseosa->id)
            ->assertJsonPath('data.items.0.nombreProducto', 'Inca Kola 1.5L')
            ->assertJsonPath('data.items.0.excedeStock', false)
            ->assertJsonPath('data.metodoPagoDetectado', 'YAPE')
            ->assertJsonPath('data.requiere_confirmacion', true)
            ->assertJsonPath('data.tokensUsados', 165);

        $this->assertEquals(13.00, (float) $response->json('data.subtotalEstimado'));
        $this->assertNotEmpty($response->json('data.mensaje_voz'));
    }

    public function test_ai_cart_resolves_peruvian_slang_fallback_chelas(): void
    {
        $cerveza = Product::query()->create([
            'name' => 'Cerveza Pilsen Callao 630ml',
            'sku' => 'BEB-010',
            'category' => 'Bebidas',
            'unit' => 'botella',
            'cost' => 5.00,
            'unit_expense' => 0,
            'selling_price' => 7.50,
            'stock' => 24,
            'low_stock_at' => 6,
            'active' => true,
            'is_public' => true,
        ]);

        Http::fake([
            'http://127.0.0.1:11434/api/chat' => Http::response([
                'message' => [
                    'content' => json_encode([
                        'items' => [],
                        'noEncontrados' => ['chelas'],
                        'metodoPago' => null,
                        'advertencias' => [],
                        'mensaje' => 'No encontré chelas.',
                    ], JSON_THROW_ON_ERROR),
                ],
                'prompt_eval_count' => 80,
                'eval_count' => 20,
            ], 200),
        ]);

        $response = $this->postJson('/api/pos/ai-cart', [
            'mensaje' => 'Ponme 2 chelas heladas',
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.productoId', $cerveza->id)
            ->assertJsonPath('data.noEncontrados', []);

        $this->assertEquals(2.0, (float) $response->json('data.items.0.cantidad'));
        $this->assertEquals(15.0, (float) $response->json('data.subtotalEstimado'));
    }

    public function test_ai_cart_returns_503_when_ollama_connection_fails(): void
    {
        Product::query()->create([
            'name' => 'Pan Francés',
            'category' => 'Panadería',
            'unit' => 'unidad',
            'cost' => 0.20,
            'unit_expense' => 0,
            'selling_price' => 0.40,
            'stock' => 50,
            'low_stock_at' => 10,
            'active' => true,
        ]);

        Http::fake(fn () => throw new ConnectionException('Connection refused'));

        $this->postJson('/api/pos/ai-cart', ['mensaje' => '2 panes'])
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'IA_OLLAMA_NO_DISPONIBLE');
    }

    public function test_ai_cart_returns_504_when_ollama_times_out(): void
    {
        Product::query()->create([
            'name' => 'Pan Francés',
            'category' => 'Panadería',
            'unit' => 'unidad',
            'cost' => 0.20,
            'unit_expense' => 0,
            'selling_price' => 0.40,
            'stock' => 50,
            'low_stock_at' => 10,
            'active' => true,
        ]);

        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out after 90000 milliseconds'));

        $this->postJson('/api/pos/ai-cart', ['mensaje' => '2 panes'])
            ->assertStatus(504)
            ->assertJsonPath('error.code', 'IA_TIMEOUT');
    }

    public function test_checkout_efectivo_calculates_change_decrements_stock_and_creates_mirror_sale(): void
    {
        $product = Product::query()->create([
            'name' => 'Aceite Primor 1L',
            'sku' => 'ABA-020',
            'category' => 'Abarrotes',
            'unit' => 'botella',
            'cost' => 6.00,
            'unit_expense' => 1.00,
            'selling_price' => 10.00,
            'stock' => 5,
            'low_stock_at' => 3,
            'active' => true,
        ]);

        $response = $this->postJson('/api/pos/checkout', [
            'items' => [
                [
                    'productoId' => $product->id,
                    'cantidad' => 3,
                ],
            ],
            'metodoPago' => 'EFECTIVO',
            'montoRecibido' => 50,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.pago.metodoPago', 'EFECTIVO')
            ->assertJsonCount(1, 'data.alertasStock')
            ->assertJsonPath('data.alertasStock.0.productoId', $product->id);

        $this->assertEquals(30.0, (float) $response->json('data.venta.total'));
        $this->assertEquals(50.0, (float) $response->json('data.pago.montoRecibido'));
        $this->assertEquals(20.0, (float) $response->json('data.pago.vuelto'));

        $product->refresh();
        $this->assertEquals(2.0, (float) $product->stock);

        $this->assertDatabaseCount('pos_sales', 1);
        $this->assertDatabaseCount('pos_sale_items', 1);
        $this->assertDatabaseHas('sales', [
            'product_id' => $product->id,
            'status' => 'paid',
        ]);

        $mirrorSale = Sale::query()->firstOrFail();
        $this->assertEquals(7.0, (float) $mirrorSale->unit_cost);
        $this->assertEquals(30.0, (float) $mirrorSale->total);
    }

    public function test_checkout_fiado_requires_client_and_increments_pending_balance(): void
    {
        $product = Product::query()->create([
            'name' => 'Azúcar Rubia 1kg',
            'category' => 'Abarrotes',
            'unit' => 'bolsa',
            'cost' => 3.00,
            'unit_expense' => 0,
            'selling_price' => 4.50,
            'stock' => 10,
            'low_stock_at' => 2,
            'active' => true,
        ]);

        $this->postJson('/api/pos/checkout', [
            'items' => [
                ['productoId' => $product->id, 'cantidad' => 2],
            ],
            'metodoPago' => 'FIADO',
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'CLIENTE_REQUERIDO');

        $response = $this->postJson('/api/pos/checkout', [
            'items' => [
                ['productoId' => $product->id, 'cantidad' => 2],
            ],
            'metodoPago' => 'FIADO',
            'clienteNombre' => 'María Vecina',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.venta.metodoPago', 'FIADO')
            ->assertJsonPath('data.venta.clienteNombre', 'María Vecina');

        $client = Client::query()->where('name', 'María Vecina')->firstOrFail();
        $this->assertSame('active', $client->status);
        $this->assertSame('pos', $client->source);
        $this->assertEquals(9.0, (float) $client->pending_balance);

        $this->assertDatabaseHas('sales', [
            'product_id' => $product->id,
            'client_id' => $client->id,
            'status' => 'pending',
        ]);
    }

    public function test_checkout_free_sale_item_without_product_id(): void
    {
        $response = $this->postJson('/api/pos/checkout', [
            'items' => [
                [
                    'productoId' => null,
                    'nombreProducto' => 'Bolsa de hielo',
                    'cantidad' => 2,
                    'precioUnitario' => 3.50,
                ],
            ],
            'metodoPago' => 'YAPE',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.venta.items.0.productoId', null)
            ->assertJsonPath('data.venta.items.0.nombreProducto', 'Bolsa de hielo');

        $this->assertEquals(7.0, (float) $response->json('data.venta.total'));
        $this->assertDatabaseHas('pos_sale_items', [
            'product_id' => null,
            'product_name' => 'Bolsa de hielo',
        ]);
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_checkout_returns_409_when_stock_is_insufficient(): void
    {
        $product = Product::query()->create([
            'name' => 'Atún Florida',
            'category' => 'Abarrotes',
            'unit' => 'lata',
            'cost' => 4.00,
            'unit_expense' => 0,
            'selling_price' => 6.00,
            'stock' => 2,
            'low_stock_at' => 1,
            'active' => true,
        ]);

        $this->postJson('/api/pos/checkout', [
            'items' => [
                ['productoId' => $product->id, 'cantidad' => 5],
            ],
            'metodoPago' => 'EFECTIVO',
            'montoRecibido' => 50,
        ])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'STOCK_INSUFICIENTE');

        $product->refresh();
        $this->assertEquals(2.0, (float) $product->stock);
        $this->assertSame(0, PosSale::query()->count());
    }

    public function test_checkout_returns_422_when_cash_amount_received_is_insufficient(): void
    {
        $product = Product::query()->create([
            'name' => 'Fideos Don Vittorio',
            'category' => 'Abarrotes',
            'unit' => 'bolsa',
            'cost' => 2.50,
            'unit_expense' => 0,
            'selling_price' => 4.00,
            'stock' => 10,
            'low_stock_at' => 2,
            'active' => true,
        ]);

        $this->postJson('/api/pos/checkout', [
            'items' => [
                ['productoId' => $product->id, 'cantidad' => 5],
            ],
            'metodoPago' => 'EFECTIVO',
            'montoRecibido' => 15,
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'MONTO_INSUFICIENTE');

        $product->refresh();
        $this->assertEquals(10.0, (float) $product->stock);
        $this->assertSame(0, PosSale::query()->count());
    }
}
