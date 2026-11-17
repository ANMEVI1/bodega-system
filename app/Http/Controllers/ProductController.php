<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
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

        $products = $query->orderBy('name')->paginate(12)->withQueryString();

        $categoryList = Category::where('active', true)->orderBy('name')->pluck('name')
            ->merge(Product::query()->distinct()->pluck('category'))
            ->filter()->unique()->values();

        $totalActive = Product::where('active', true)->count();
        $lowStockCount = Product::where('active', true)->where('stock', '>', 0)->whereColumn('stock', '<=', 'low_stock_at')->count();
        $outOfStockCount = Product::where('active', true)->where('stock', '<=', 0)->count();
        $totalInventoryCost = (float) (Product::where('active', true)->selectRaw('SUM(stock * cost) as total')->value('total') ?? 0);

        return view('admin.products.index', [
            'products' => $products,
            'categories' => $categoryList,
            'totalActive' => $totalActive,
            'lowStockCount' => $lowStockCount,
            'outOfStockCount' => $outOfStockCount,
            'totalInventoryCost' => $totalInventoryCost,
        ]);
    }

    public function create(): View
    {
        return view('admin.products.form', [
            'product' => new Product(['active' => true, 'is_public' => false]),
            'categories' => Category::where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $product = Product::query()->create($this->validated($request));

        return redirect()->route('admin.products.index')->with('success', "Producto «{$product->name}» registrado exitosamente.");
    }

    public function show(Product $product): View
    {
        $product->load(['categoryRelation', 'supplier']);

        $unitsSold = (float) ($product->sales()->where('status', 'paid')->sum('quantity') ?? 0);
        $totalRevenue = (float) ($product->sales()->where('status', 'paid')->sum('total') ?? 0);
        $unitCostTotal = (float) $product->cost + (float) $product->unit_expense;
        $unitProfit = (float) $product->selling_price - $unitCostTotal;
        $marginPercent = (float) $product->selling_price > 0 ? round(($unitProfit / (float) $product->selling_price) * 100, 1) : 0;
        $totalGrossProfit = $unitsSold * $unitProfit;

        $recentSales = $product->sales()->latest('sold_at')->limit(8)->get();
        $recentPurchases = $product->supplierPurchases()->with('supplier')->latest()->limit(5)->get();

        return view('admin.products.show', compact(
            'product',
            'unitsSold',
            'totalRevenue',
            'unitCostTotal',
            'unitProfit',
            'marginPercent',
            'totalGrossProfit',
            'recentSales',
            'recentPurchases'
        ));
    }

    public function edit(Product $product): View
    {
        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request, $product));

        return redirect()->route('admin.products.index')->with('success', 'Inventario actualizado.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->sales()->exists() || $product->supplierPurchases()->exists()) {
            return back()->withErrors(['product' => 'Este producto ya tiene movimientos registrados (ventas o compras). Puedes desactivarlo en lugar de eliminarlo para conservar el historial.']);
        }
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Producto eliminado.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'sku' => ['nullable', 'string', 'max:80', Rule::unique('products', 'sku')->ignore($product)],
            'category_id' => ['nullable', 'exists:categories,id'],
            'category' => ['nullable', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:40'],
            'cost' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'unit_expense' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'margin' => ['required', 'numeric', 'min:0', 'max:95'],
            'stock' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'low_stock_at' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'active' => ['nullable', 'boolean'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        // Synchronize category string & category_id
        if (!empty($validated['category_id'])) {
            $cat = Category::find($validated['category_id']);
            if ($cat) {
                $validated['category'] = $cat->name;
            }
        } elseif (!empty($validated['category'])) {
            $cat = Category::where('name', $validated['category'])->first();
            if ($cat) {
                $validated['category_id'] = $cat->id;
            }
        } else {
            $validated['category'] = 'Abarrotes';
            $defaultCat = Category::where('name', 'Abarrotes')->first();
            if ($defaultCat) {
                $validated['category_id'] = $defaultCat->id;
            }
        }

        $cost = (float) $validated['cost'];
        $validated['unit_expense'] = (float) ($validated['unit_expense'] ?? 0);
        $margin = (float) $validated['margin'];
        $validated['selling_price'] = round(($cost + $validated['unit_expense']) / (1 - $margin / 100), 2);
        $validated['active'] = $request->boolean('active');
        $validated['is_public'] = $request->boolean('is_public'); // strictly defaults to false!
        unset($validated['margin']);

        return $validated;
    }
}
