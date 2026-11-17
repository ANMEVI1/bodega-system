<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    /**
     * Módulo de Inventario: Vista de control de existencias, semáforos de stock y valuación de almacén.
     * Consulta el inventario sin modificar los datos maestros del producto.
     */
    public function index(Request $request): View
    {
        $stockFilter = $request->string('stock_status')->trim()->value();

        $query = Product::query()
            ->with('categoryRelation')
            ->withSum(['sales as units_sold' => fn ($q) => $q->where('status', 'paid')], 'quantity')
            ->when($request->string('q')->trim()->value(), function ($q, $term) {
                $q->where(fn ($sub) => $sub->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('category', 'like', "%{$term}%"));
            })
            ->when($request->string('category')->trim()->value(), fn ($q, $cat) => $q->where('category', $cat))
            ->when($request->has('active') && $request->input('active') !== '', fn ($q) => $q->where('active', $request->boolean('active')));

        if ($stockFilter === 'low') {
            $query->where('active', true)->where('stock', '>', 0)->whereColumn('stock', '<=', 'low_stock_at');
        } elseif ($stockFilter === 'out') {
            $query->where('active', true)->where('stock', '<=', 0);
        } elseif ($stockFilter === 'in_stock') {
            $query->where('active', true)->whereColumn('stock', '>', 'low_stock_at');
        }

        $products = $query->orderBy('stock', 'asc')->paginate(15)->withQueryString();

        $categoryList = Category::where('active', true)->orderBy('name')->pluck('name')
            ->merge(Product::query()->distinct()->pluck('category'))
            ->filter()->unique()->values();

        $totalUnitsInStock = (float) (Product::where('active', true)->sum('stock') ?? 0);
        $lowStockCount = Product::where('active', true)->where('stock', '>', 0)->whereColumn('stock', '<=', 'low_stock_at')->count();
        $outOfStockCount = Product::where('active', true)->where('stock', '<=', 0)->count();
        $totalInventoryCost = (float) (Product::where('active', true)->selectRaw('SUM(stock * cost) as total')->value('total') ?? 0);

        return view('admin.inventory.index', [
            'products' => $products,
            'categories' => $categoryList,
            'totalUnitsInStock' => $totalUnitsInStock,
            'lowStockCount' => $lowStockCount,
            'outOfStockCount' => $outOfStockCount,
            'totalInventoryCost' => $totalInventoryCost,
        ]);
    }
}
