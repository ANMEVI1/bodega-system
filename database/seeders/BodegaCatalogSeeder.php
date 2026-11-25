<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Database\Seeder;

class BodegaCatalogSeeder extends Seeder
{
    /**
     * Seed a complete 40-product Peruvian bodega catalog across 8 categories and 6 sample clients.
     */
    public function run(): void
    {
        $this->call(DatabaseSeeder::class);
        $this->call(CategorySeeder::class);

        $categoriesByName = Category::query()->pluck('id', 'name');

        $products = [
            // Abarrotes (5)
            ['sku' => 'ABR-001', 'name' => 'Arroz Extra Costeño 5 kg', 'category' => 'Abarrotes', 'unit' => 'bolsa', 'cost' => 17.50, 'unit_expense' => 0.50, 'selling_price' => 23.50, 'stock' => 28, 'low_stock_at' => 6, 'active' => true, 'is_public' => true],
            ['sku' => 'ABR-002', 'name' => 'Aceite Vegetal Primor 1 L', 'category' => 'Abarrotes', 'unit' => 'botella', 'cost' => 8.10, 'unit_expense' => 0.30, 'selling_price' => 11.20, 'stock' => 4, 'low_stock_at' => 6, 'active' => true, 'is_public' => true],
            ['sku' => 'ABR-003', 'name' => 'Azúcar Rubia Cartavio 1 kg', 'category' => 'Abarrotes', 'unit' => 'bolsa', 'cost' => 3.40, 'unit_expense' => 0.15, 'selling_price' => 4.80, 'stock' => 42, 'low_stock_at' => 10, 'active' => true, 'is_public' => true],
            ['sku' => 'ABR-004', 'name' => 'Quinua Perlada Andina 1 kg', 'category' => 'Abarrotes', 'unit' => 'bolsa', 'cost' => 9.20, 'unit_expense' => 0.35, 'selling_price' => 13.80, 'stock' => 15, 'low_stock_at' => 4, 'active' => true, 'is_public' => true],
            ['sku' => 'ABR-005', 'name' => 'Fideo Don Vittorio Spaghetti 500 g', 'category' => 'Abarrotes', 'unit' => 'paquete', 'cost' => 2.60, 'unit_expense' => 0.15, 'selling_price' => 3.80, 'stock' => 34, 'low_stock_at' => 8, 'active' => true, 'is_public' => true],

            // Lácteos (5)
            ['sku' => 'LAC-001', 'name' => 'Leche Evaporada Gloria Azul', 'category' => 'Lácteos', 'unit' => 'lata', 'cost' => 3.55, 'unit_expense' => 0.15, 'selling_price' => 4.60, 'stock' => 54, 'low_stock_at' => 12, 'active' => true, 'is_public' => true],
            ['sku' => 'LAC-002', 'name' => 'Queso Fresco Artesanal 500 g', 'category' => 'Lácteos', 'unit' => 'molde', 'cost' => 11.00, 'unit_expense' => 0.60, 'selling_price' => 15.50, 'stock' => 3, 'low_stock_at' => 5, 'active' => true, 'is_public' => true],
            ['sku' => 'LAC-003', 'name' => 'Yogurt Gloria Fresa 1 L', 'category' => 'Lácteos', 'unit' => 'botella', 'cost' => 5.40, 'unit_expense' => 0.25, 'selling_price' => 7.50, 'stock' => 18, 'low_stock_at' => 5, 'active' => true, 'is_public' => true],
            ['sku' => 'LAC-004', 'name' => 'Mantequilla Laive Con Sal 200 g', 'category' => 'Lácteos', 'unit' => 'barra', 'cost' => 6.20, 'unit_expense' => 0.25, 'selling_price' => 8.80, 'stock' => 4, 'low_stock_at' => 5, 'active' => true, 'is_public' => true],
            ['sku' => 'LAC-005', 'name' => 'Leche Condensada Nestlé 393 g', 'category' => 'Lácteos', 'unit' => 'lata', 'cost' => 4.80, 'unit_expense' => 0.20, 'selling_price' => 6.90, 'stock' => 24, 'low_stock_at' => 6, 'active' => true, 'is_public' => true],

            // Bebidas (5)
            ['sku' => 'BEB-001', 'name' => 'Café Pasado Orgánico Chanchamayo', 'category' => 'Bebidas', 'unit' => 'bolsa', 'cost' => 14.20, 'unit_expense' => 0.50, 'selling_price' => 21.00, 'stock' => 16, 'low_stock_at' => 4, 'active' => true, 'is_public' => true],
            ['sku' => 'BEB-002', 'name' => 'Agua Mineral San Mateo 2.5 L', 'category' => 'Bebidas', 'unit' => 'botella', 'cost' => 2.20, 'unit_expense' => 0.10, 'selling_price' => 3.50, 'stock' => 36, 'low_stock_at' => 8, 'active' => true, 'is_public' => true],
            ['sku' => 'BEB-003', 'name' => 'Inca Kola 1.5 L', 'category' => 'Bebidas', 'unit' => 'botella', 'cost' => 4.90, 'unit_expense' => 0.20, 'selling_price' => 6.80, 'stock' => 30, 'low_stock_at' => 8, 'active' => true, 'is_public' => true],
            ['sku' => 'BEB-004', 'name' => 'Coca-Cola 600 ml', 'category' => 'Bebidas', 'unit' => 'botella', 'cost' => 2.10, 'unit_expense' => 0.10, 'selling_price' => 3.20, 'stock' => 48, 'low_stock_at' => 10, 'active' => true, 'is_public' => true],
            ['sku' => 'BEB-005', 'name' => 'Cerveza Pilsen Callao 620 ml', 'category' => 'Bebidas', 'unit' => 'botella', 'cost' => 5.20, 'unit_expense' => 0.25, 'selling_price' => 7.50, 'stock' => 24, 'low_stock_at' => 6, 'active' => true, 'is_public' => true],

            // Panadería (5)
            ['sku' => 'PAN-001', 'name' => 'Pan Ciabatta Masa Madre (x6)', 'category' => 'Panadería', 'unit' => 'pack', 'cost' => 4.20, 'unit_expense' => 0.30, 'selling_price' => 6.80, 'stock' => 18, 'low_stock_at' => 5, 'active' => true, 'is_public' => true],
            ['sku' => 'PAN-003', 'name' => 'Pan Francés Fresco (x6)', 'category' => 'Panadería', 'unit' => 'bolsa', 'cost' => 1.80, 'unit_expense' => 0.15, 'selling_price' => 3.00, 'stock' => 25, 'low_stock_at' => 6, 'active' => true, 'is_public' => true],
            ['sku' => 'PAN-004', 'name' => 'Pan de Molde Integral Bimbo', 'category' => 'Panadería', 'unit' => 'bolsa', 'cost' => 7.50, 'unit_expense' => 0.30, 'selling_price' => 10.50, 'stock' => 14, 'low_stock_at' => 4, 'active' => true, 'is_public' => true],
            ['sku' => 'PAN-005', 'name' => 'Tostadas Clásicas Unión', 'category' => 'Panadería', 'unit' => 'paquete', 'cost' => 3.60, 'unit_expense' => 0.20, 'selling_price' => 5.20, 'stock' => 3, 'low_stock_at' => 5, 'active' => true, 'is_public' => true],
            ['sku' => 'PAN-006', 'name' => 'Queque de Vainilla Artesanal', 'category' => 'Panadería', 'unit' => 'unidad', 'cost' => 6.00, 'unit_expense' => 0.40, 'selling_price' => 9.50, 'stock' => 0, 'low_stock_at' => 3, 'active' => true, 'is_public' => true],

            // Desayunos (5)
            ['sku' => 'PAN-002', 'name' => 'Miel de Oxapampa Pura 500 g', 'category' => 'Desayunos', 'unit' => 'frasco', 'cost' => 16.00, 'unit_expense' => 0.80, 'selling_price' => 24.00, 'stock' => 9, 'low_stock_at' => 3, 'active' => true, 'is_public' => true],
            ['sku' => 'DES-002', 'name' => 'Avena Quaker Tradicional 400 g', 'category' => 'Desayunos', 'unit' => 'bolsa', 'cost' => 4.10, 'unit_expense' => 0.20, 'selling_price' => 5.90, 'stock' => 26, 'low_stock_at' => 6, 'active' => true, 'is_public' => true],
            ['sku' => 'DES-003', 'name' => 'Café Instantáneo Kirma 50 g', 'category' => 'Desayunos', 'unit' => 'frasco', 'cost' => 6.40, 'unit_expense' => 0.25, 'selling_price' => 9.20, 'stock' => 20, 'low_stock_at' => 5, 'active' => true, 'is_public' => true],
            ['sku' => 'DES-004', 'name' => 'Mermelada de Fresa Gloria 315 g', 'category' => 'Desayunos', 'unit' => 'frasco', 'cost' => 5.30, 'unit_expense' => 0.25, 'selling_price' => 7.80, 'stock' => 16, 'low_stock_at' => 4, 'active' => true, 'is_public' => true],
            ['sku' => 'DES-005', 'name' => 'Cereal Angel Flakes 320 g', 'category' => 'Desayunos', 'unit' => 'caja', 'cost' => 7.20, 'unit_expense' => 0.30, 'selling_price' => 10.40, 'stock' => 4, 'low_stock_at' => 5, 'active' => true, 'is_public' => true],

            // Limpieza (5)
            ['sku' => 'LIM-001', 'name' => 'Detergente Líquido Marsella 2 L', 'category' => 'Limpieza', 'unit' => 'botella', 'cost' => 15.50, 'unit_expense' => 0.50, 'selling_price' => 22.00, 'stock' => 14, 'low_stock_at' => 4, 'active' => true, 'is_public' => true],
            ['sku' => 'LIM-002', 'name' => 'Detergente Bolívar Floral 380 g', 'category' => 'Limpieza', 'unit' => 'bolsa', 'cost' => 3.40, 'unit_expense' => 0.15, 'selling_price' => 5.00, 'stock' => 32, 'low_stock_at' => 8, 'active' => true, 'is_public' => true],
            ['sku' => 'LIM-003', 'name' => 'Lejía Clorox Tradicional 1 L', 'category' => 'Limpieza', 'unit' => 'botella', 'cost' => 3.90, 'unit_expense' => 0.20, 'selling_price' => 5.80, 'stock' => 22, 'low_stock_at' => 6, 'active' => true, 'is_public' => true],
            ['sku' => 'LIM-004', 'name' => 'Lavavajillas Ayudín Limón 500 ml', 'category' => 'Limpieza', 'unit' => 'botella', 'cost' => 4.60, 'unit_expense' => 0.20, 'selling_price' => 6.80, 'stock' => 5, 'low_stock_at' => 5, 'active' => true, 'is_public' => true],
            ['sku' => 'LIM-005', 'name' => 'Papel Higiénico Suave (x4)', 'category' => 'Limpieza', 'unit' => 'paquete', 'cost' => 5.20, 'unit_expense' => 0.25, 'selling_price' => 7.50, 'stock' => 28, 'low_stock_at' => 6, 'active' => true, 'is_public' => true],

            // Snacks y Golosinas (5)
            ['sku' => 'SNK-001', 'name' => 'Galletas Integrales Avena y Miel', 'category' => 'Snacks y Golosinas', 'unit' => 'paquete', 'cost' => 3.80, 'unit_expense' => 0.20, 'selling_price' => 6.20, 'stock' => 22, 'low_stock_at' => 5, 'active' => true, 'is_public' => true],
            ['sku' => 'SNK-002', 'name' => 'Galleta Oreo Pack 36 g', 'category' => 'Snacks y Golosinas', 'unit' => 'paquete', 'cost' => 1.20, 'unit_expense' => 0.10, 'selling_price' => 2.00, 'stock' => 55, 'low_stock_at' => 12, 'active' => true, 'is_public' => true],
            ['sku' => 'SNK-003', 'name' => 'Galleta Soda Field (x6)', 'category' => 'Snacks y Golosinas', 'unit' => 'pack', 'cost' => 3.20, 'unit_expense' => 0.15, 'selling_price' => 4.80, 'stock' => 30, 'low_stock_at' => 8, 'active' => true, 'is_public' => true],
            ['sku' => 'SNK-004', 'name' => 'Papas Lays Clásicas 150 g', 'category' => 'Snacks y Golosinas', 'unit' => 'bolsa', 'cost' => 4.80, 'unit_expense' => 0.20, 'selling_price' => 6.90, 'stock' => 19, 'low_stock_at' => 5, 'active' => true, 'is_public' => true],
            ['sku' => 'SNK-005', 'name' => 'Chocolate Sublime Clásico 30 g', 'category' => 'Snacks y Golosinas', 'unit' => 'barra', 'cost' => 1.50, 'unit_expense' => 0.10, 'selling_price' => 2.50, 'stock' => 0, 'low_stock_at' => 8, 'active' => true, 'is_public' => true],

            // Cuidado Personal (5)
            ['sku' => 'CUI-001', 'name' => 'Jabón de Tocador Bolívar Glicerina', 'category' => 'Cuidado Personal', 'unit' => 'barra', 'cost' => 2.30, 'unit_expense' => 0.10, 'selling_price' => 3.60, 'stock' => 40, 'low_stock_at' => 10, 'active' => true, 'is_public' => true],
            ['sku' => 'CUI-002', 'name' => 'Pasta Dental Colgate Triple Acción 75 ml', 'category' => 'Cuidado Personal', 'unit' => 'tubo', 'cost' => 4.90, 'unit_expense' => 0.20, 'selling_price' => 7.20, 'stock' => 24, 'low_stock_at' => 6, 'active' => true, 'is_public' => true],
            ['sku' => 'CUI-003', 'name' => 'Shampoo Head & Shoulders 375 ml', 'category' => 'Cuidado Personal', 'unit' => 'frasco', 'cost' => 14.50, 'unit_expense' => 0.50, 'selling_price' => 20.50, 'stock' => 12, 'low_stock_at' => 4, 'active' => true, 'is_public' => true],
            ['sku' => 'CUI-004', 'name' => 'Desodorante Rexona Men Roll-On 50 ml', 'category' => 'Cuidado Personal', 'unit' => 'unidad', 'cost' => 8.80, 'unit_expense' => 0.30, 'selling_price' => 12.90, 'stock' => 15, 'low_stock_at' => 4, 'active' => true, 'is_public' => true],
            ['sku' => 'CUI-005', 'name' => 'Alcohol Medicinal 70° 250 ml', 'category' => 'Cuidado Personal', 'unit' => 'frasco', 'cost' => 3.80, 'unit_expense' => 0.20, 'selling_price' => 5.90, 'stock' => 18, 'low_stock_at' => 5, 'active' => true, 'is_public' => true],
        ];

        foreach ($products as $item) {
            Product::query()->firstOrCreate(
                ['sku' => $item['sku']],
                $item + ['category_id' => $categoriesByName[$item['category']] ?? null]
            );
        }

        $clients = [
            ['name' => 'María Elena Quispe', 'phone' => '51945112233', 'email' => 'maria.quispe@correo.pe', 'status' => 'active', 'notes' => 'Compras semanales para su familia los sábados.'],
            ['name' => 'Restaurante El Mirador', 'client_type' => 'business', 'document_type' => 'ruc', 'document_number' => '20123456789', 'contact_name' => 'Jorge Navarro', 'phone' => '51988224466', 'email' => 'compras@elmirador.pe', 'address' => 'Av. Principal 245', 'district' => 'San Isidro', 'city' => 'Lima', 'preferred_contact' => 'whatsapp', 'source' => 'referral', 'status' => 'active', 'notes' => 'Pedido recurrente de abarrotes y aceite.'],
            ['name' => 'Carlos Mendoza', 'phone' => '51912778899', 'email' => null, 'status' => 'follow-up', 'notes' => 'Consulta precios por mayor en lácteos.'],
            ['name' => 'Lucía Paredes', 'phone' => '51965334411', 'email' => 'lucia.p@correo.pe', 'status' => 'active', 'notes' => 'Prefiere entregas rápidas por WhatsApp.'],
            ['name' => 'Rosaura Chávez', 'phone' => '51974556677', 'email' => 'rosaura.chavez@correo.pe', 'status' => 'active', 'notes' => 'Cliente frecuente del barrio, compra pan y lácteos cada mañana.'],
            ['name' => 'Bodega & Cafetería San Martín', 'client_type' => 'business', 'document_type' => 'ruc', 'document_number' => '20548912345', 'contact_name' => 'Martín Huamán', 'phone' => '51956889900', 'email' => 'pedidos@cafeteriasanmartin.pe', 'address' => 'Jr. San Martín 410', 'district' => 'Lince', 'city' => 'Lima', 'preferred_contact' => 'whatsapp', 'source' => 'walk-in', 'status' => 'active', 'notes' => 'Abastecimiento semanal de café, panes, gaseosas y snacks.'],
        ];

        foreach ($clients as $client) {
            Client::query()->firstOrCreate(['phone' => $client['phone']], $client);
        }
    }
}
