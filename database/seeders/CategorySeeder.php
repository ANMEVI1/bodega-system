<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Abarrotes', 'description' => 'Arroz, azúcar, fideos, aceites y conservas.', 'color' => 'amber'],
            ['name' => 'Lácteos', 'description' => 'Leche, quesos, mantequillas y yogures.', 'color' => 'sky'],
            ['name' => 'Bebidas', 'description' => 'Aguas, gaseosas, jugos y cafés.', 'color' => 'violet'],
            ['name' => 'Panadería', 'description' => 'Panes frescos, tostadas y bollos.', 'color' => 'amber'],
            ['name' => 'Desayunos', 'description' => 'Mieles, mermeladas, cereales y avenas.', 'color' => 'rose'],
            ['name' => 'Limpieza', 'description' => 'Detergentes, desinfectantes y útiles de aseo.', 'color' => 'emerald'],
            ['name' => 'Snacks y Golosinas', 'description' => 'Galletas, chocolates y piqueos variados.', 'color' => 'fuchsia'],
            ['name' => 'Cuidado Personal', 'description' => 'Jabones, pastas dentales y cuidado diario.', 'color' => 'sky'],
        ];

        foreach ($categories as $data) {
            $cat = Category::query()->firstOrCreate(
                ['name' => $data['name']],
                $data + ['slug' => Str::slug($data['name']), 'active' => true]
            );

            // Link existing products that have this category name
            Product::query()->where('category', $data['name'])->update(['category_id' => $cat->id]);
        }
    }
}

