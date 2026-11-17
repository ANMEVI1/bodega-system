<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SupplierPurchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierPurchaseController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'unit_cost' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'purchased_at' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($data): void {
            $product = Product::query()->lockForUpdate()->findOrFail($data['product_id']);
            $quantity = (float) $data['quantity'];
            $unitCost = (float) $data['unit_cost'];
            SupplierPurchase::query()->create([...$data, 'total' => round($quantity * $unitCost, 2)]);
            $product->increment('stock', $quantity);
            $product->update(['cost' => $unitCost]);
        });

        return redirect()->route('admin.suppliers.index')->with('success', 'Compra registrada y existencias actualizadas.');
    }
}
