<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ContactSubmission;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SiteSection;
use App\Models\Supplier;
use App\Models\SupplierPurchase;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * Seed realistic sample catalog, clients, suppliers, purchases, and 6-month sales.
     * Deliberately leaves users empty so /setup stays available for the owner.
     */
    public function run(): void
    {
        $this->call(DatabaseSeeder::class);

        SiteSection::query()->where('section', 'contact')->update([
            'whatsapp' => '51987654321',
        ]);

        $products = collect([
            ['sku' => 'ABR-001', 'name' => 'Arroz Extra Costeño 5 kg', 'category' => 'Abarrotes', 'unit' => 'bolsa', 'cost' => 17.50, 'unit_expense' => 0.50, 'selling_price' => 23.50, 'stock' => 28, 'low_stock_at' => 6, 'active' => true, 'is_public' => true],
            ['sku' => 'ABR-002', 'name' => 'Aceite Vegetal Primor 1 L', 'category' => 'Abarrotes', 'unit' => 'botella', 'cost' => 8.10, 'unit_expense' => 0.30, 'selling_price' => 11.20, 'stock' => 4, 'low_stock_at' => 6, 'active' => true, 'is_public' => true],
            ['sku' => 'ABR-003', 'name' => 'Azúcar Rubia Cartavio 1 kg', 'category' => 'Abarrotes', 'unit' => 'bolsa', 'cost' => 3.40, 'unit_expense' => 0.15, 'selling_price' => 4.80, 'stock' => 42, 'low_stock_at' => 10, 'active' => true, 'is_public' => true],
            ['sku' => 'LAC-001', 'name' => 'Leche Evaporada Gloria Azul', 'category' => 'Lácteos', 'unit' => 'lata', 'cost' => 3.55, 'unit_expense' => 0.15, 'selling_price' => 4.60, 'stock' => 54, 'low_stock_at' => 12, 'active' => true, 'is_public' => true],
            ['sku' => 'LAC-002', 'name' => 'Queso Fresco Artesanal 500 g', 'category' => 'Lácteos', 'unit' => 'molde', 'cost' => 11.00, 'unit_expense' => 0.60, 'selling_price' => 15.50, 'stock' => 3, 'low_stock_at' => 5, 'active' => true, 'is_public' => true],
            ['sku' => 'BEB-001', 'name' => 'Café Pasado Orgánico Chanchamayo', 'category' => 'Bebidas', 'unit' => 'bolsa', 'cost' => 14.20, 'unit_expense' => 0.50, 'selling_price' => 21.00, 'stock' => 16, 'low_stock_at' => 4, 'active' => true, 'is_public' => true],
            ['sku' => 'BEB-002', 'name' => 'Agua Mineral San Mateo 2.5 L', 'category' => 'Bebidas', 'unit' => 'botella', 'cost' => 2.20, 'unit_expense' => 0.10, 'selling_price' => 3.50, 'stock' => 36, 'low_stock_at' => 8, 'active' => true, 'is_public' => true],
            ['sku' => 'PAN-001', 'name' => 'Pan Ciabatta Masa Madre (x6)', 'category' => 'Panadería', 'unit' => 'pack', 'cost' => 4.20, 'unit_expense' => 0.30, 'selling_price' => 6.80, 'stock' => 18, 'low_stock_at' => 5, 'active' => true, 'is_public' => true],
            ['sku' => 'PAN-002', 'name' => 'Miel de Oxapampa Pura 500 g', 'category' => 'Desayunos', 'unit' => 'frasco', 'cost' => 16.00, 'unit_expense' => 0.80, 'selling_price' => 24.00, 'stock' => 9, 'low_stock_at' => 3, 'active' => true, 'is_public' => true],
            ['sku' => 'LIM-001', 'name' => 'Detergente Líquido Marsella 2 L', 'category' => 'Limpieza', 'unit' => 'botella', 'cost' => 15.50, 'unit_expense' => 0.50, 'selling_price' => 22.00, 'stock' => 14, 'low_stock_at' => 4, 'active' => true, 'is_public' => true],
        ])->map(fn (array $item) => Product::query()->firstOrCreate(['sku' => $item['sku']], $item));

        $clients = collect([
            ['name' => 'María Elena Quispe', 'phone' => '51945112233', 'email' => 'maria.quispe@correo.pe', 'status' => 'active', 'notes' => 'Compras semanales para su familia los sábados.'],
            ['name' => 'Restaurante El Mirador', 'client_type' => 'business', 'document_type' => 'ruc', 'document_number' => '20123456789', 'contact_name' => 'Jorge Navarro', 'phone' => '51988224466', 'email' => 'compras@elmirador.pe', 'address' => 'Av. Principal 245', 'district' => 'San Isidro', 'city' => 'Lima', 'preferred_contact' => 'whatsapp', 'source' => 'referral', 'status' => 'active', 'notes' => 'Pedido recurrente de abarrotes y aceite.'],
            ['name' => 'Carlos Mendoza', 'phone' => '51912778899', 'email' => null, 'status' => 'follow-up', 'notes' => 'Consulta precios por mayor en lácteos.'],
            ['name' => 'Lucía Paredes', 'phone' => '51965334411', 'email' => 'lucia.p@correo.pe', 'status' => 'active', 'notes' => 'Prefiere entregas rápidas por WhatsApp.'],
        ])->map(fn (array $item) => Client::query()->firstOrCreate(['phone' => $item['phone']], $item));

        $suppliers = collect([
            ['name' => 'Distribuidora Santa Clara', 'contact_name' => 'Jorge Navarro', 'phone' => '51999110022', 'email' => 'ventas@santaclara.pe', 'specialty' => 'Abarrotes y aceites', 'notes' => 'Reparto martes y viernes sin recargo.'],
            ['name' => 'Lácteos del Valle SRL', 'contact_name' => 'Paola Díaz', 'phone' => '51977445566', 'email' => 'pedidos@lacteosdelvalle.pe', 'specialty' => 'Lácteos y quesos frescos', 'notes' => 'Entrega temprana 7:00 a.m.'],
            ['name' => 'Tostaduría Selva Alta', 'contact_name' => 'Mateo Ríos', 'phone' => '51955332211', 'email' => 'hola@selvaalta.pe', 'specialty' => 'Café, miel y orgánicos', 'notes' => 'Lotes artesanales semanales.'],
        ])->map(fn (array $item) => Supplier::query()->firstOrCreate(['phone' => $item['phone']], $item));

        if (SupplierPurchase::query()->count() === 0) {
            foreach ([
                [$suppliers[0], $products[0], 20, 17.50, 18, 'FAC-2041'],
                [$suppliers[0], $products[1], 12, 8.10, 11, 'FAC-2058'],
                [$suppliers[1], $products[3], 36, 3.55, 7, 'GR-0891'],
                [$suppliers[2], $products[5], 10, 14.20, 4, 'BOL-412'],
            ] as [$supplier, $product, $qty, $cost, $daysAgo, $ref]) {
                SupplierPurchase::query()->create([
                    'supplier_id' => $supplier->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_cost' => $cost,
                    'total' => round($qty * $cost, 2),
                    'purchased_at' => now()->subDays($daysAgo)->toDateString(),
                    'reference' => $ref,
                ]);
            }
        }

        if (Sale::query()->count() === 0) {
            $salesPlan = [
                ['NV-DEMO-01', $clients[1], $products[0], 14, 150, 'paid'],
                ['NV-DEMO-02', $clients[0], $products[3], 24, 145, 'paid'],
                ['NV-DEMO-03', $clients[1], $products[1], 18, 120, 'paid'],
                ['NV-DEMO-04', $clients[3], $products[5], 8, 115, 'paid'],
                ['NV-DEMO-05', $clients[1], $products[0], 22, 90, 'paid'],
                ['NV-DEMO-06', $clients[2], $products[3], 30, 86, 'paid'],
                ['NV-DEMO-07', $clients[0], $products[8], 9, 60, 'paid'],
                ['NV-DEMO-08', $clients[1], $products[1], 24, 54, 'paid'],
                ['NV-DEMO-09', $clients[3], $products[7], 16, 32, 'paid'],
                ['NV-DEMO-10', $clients[1], $products[0], 20, 28, 'paid'],
                ['NV-DEMO-11', $clients[0], $products[5], 11, 14, 'paid'],
                ['NV-DEMO-12', $clients[1], $products[2], 32, 9, 'paid'],
                ['NV-DEMO-13', $clients[3], $products[8], 7, 5, 'paid'],
                ['NV-DEMO-14', $clients[2], $products[9], 6, 3, 'paid'],
                ['NV-DEMO-15', null, $products[7], 12, 1, 'paid'],
                ['NV-DEMO-16', $clients[2], $products[4], 4, 0, 'pending'],
            ];

            foreach ($salesPlan as [$ref, $client, $product, $qty, $daysAgo, $status]) {
                $unitCost = (float) $product->cost + (float) $product->unit_expense;
                $unitPrice = (float) $product->selling_price;
                Sale::query()->create([
                    'reference' => $ref,
                    'client_id' => $client?->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'unit_cost' => $unitCost,
                    'total' => round($unitPrice * $qty, 2),
                    'sold_at' => now()->subDays($daysAgo)->toDateString(),
                    'status' => $status,
                ]);
            }
        }

        if (ContactSubmission::query()->count() === 0) {
            ContactSubmission::query()->create([
                'name' => 'Valeria Torres',
                'phone' => '51933445566',
                'email' => 'valeria.torres@correo.pe',
                'message' => 'Hola, quisiera saber si tienen Arroz Extra Costeño 5 kg y Café de Chanchamayo para recoger hoy por la tarde.',
                'status' => 'new',
            ]);
            ContactSubmission::query()->create([
                'name' => 'Diego Salazar',
                'phone' => '51921998877',
                'email' => null,
                'message' => 'Buenas, ¿hacen precio especial por caja de leche evaporada para evento vecinal?',
                'status' => 'new',
            ]);
        }
    }
}
