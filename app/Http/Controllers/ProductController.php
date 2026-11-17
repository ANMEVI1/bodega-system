<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()->withSum(['sales as units_sold' => fn ($query) => $query->where('status', 'paid')], 'quantity')
            ->when($request->string('q')->trim()->value(), fn ($query, $term) => $query->where(fn ($query) => $query->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%")->orWhere('category', 'like', "%{$term}%")))
            ->when($request->string('category')->trim()->value(), fn ($query, $category) => $query->where('category', $category))
            ->orderBy('name')->paginate(12)->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Product::query()->distinct()->orderBy('category')->pluck('category'),
            'lowStockCount' => Product::query()->where('active', true)->whereColumn('stock', '<=', 'low_stock_at')->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.form', ['product' => new Product]);
    }

    public function store(Request $request): RedirectResponse
    {
        Product::query()->create($this->validated($request));

        return redirect()->route('admin.products.index')->with('success', 'Producto guardado.');
    }

    public function edit(Product $product): View
    {
        return view('admin.products.form', compact('product'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request, $product));

        return redirect()->route('admin.products.index')->with('success', 'Inventario actualizado.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->sales()->exists() || $product->supplierPurchases()->exists()) {
            return back()->withErrors(['product' => 'Este producto ya tiene movimientos. Desactívalo en lugar de eliminarlo.']);
        }
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Producto eliminado.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'sku' => ['nullable', 'string', 'max:80', Rule::unique('products', 'sku')->ignore($product)],
            'category' => ['required', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:40'],
            'cost' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'unit_expense' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'margin' => ['required', 'numeric', 'min:0', 'max:95'],
            'stock' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'low_stock_at' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'active' => ['nullable', 'boolean'],
        ]);
        $cost = (float) $validated['cost'];
        $validated['unit_expense'] = (float) ($validated['unit_expense'] ?? 0);
        $margin = (float) $validated['margin'];
        $validated['selling_price'] = round(($cost + $validated['unit_expense']) / (1 - $margin / 100), 2);
        $validated['active'] = $request->boolean('active');
        unset($validated['margin']);

        return $validated;
    }
}
