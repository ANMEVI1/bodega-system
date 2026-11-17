<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $period = $request->string('period')->value();
        $sales = Sale::query()->with(['product', 'client'])
            ->when($period === 'quarter', fn ($query) => $query->whereDate('sold_at', '>=', now()->startOfQuarter()))
            ->when($period === 'month', fn ($query) => $query->whereDate('sold_at', '>=', now()->startOfMonth()))
            ->latest('sold_at')->latest()->paginate(15)->withQueryString();
        $month = Sale::query()->where('status', 'paid')->whereDate('sold_at', '>=', now()->startOfMonth());
        $quarter = Sale::query()->where('status', 'paid')->whereDate('sold_at', '>=', now()->startOfQuarter());

        return view('admin.sales.index', [
            'sales' => $sales,
            'products' => Product::query()->where('active', true)->orderBy('name')->get(),
            'clients' => Client::query()->orderBy('name')->get(),
            'monthRevenue' => (clone $month)->sum('total'),
            'monthProfit' => (clone $month)->selectRaw('COALESCE(SUM(total - quantity * unit_cost), 0) as profit')->value('profit'),
            'quarterRevenue' => $quarter->sum('total'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'unit_price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'sold_at' => ['required', 'date'],
            'status' => ['required', 'in:paid,pending'],
        ]);

        DB::transaction(function () use ($data): void {
            $product = Product::query()->lockForUpdate()->findOrFail($data['product_id']);
            $quantity = (float) $data['quantity'];
            abort_unless($product->active, 422, 'El producto está inactivo.');
            abort_if((float) $product->stock < $quantity, 422, 'No hay suficiente inventario para esta venta.');
            $unitPrice = isset($data['unit_price']) && $data['unit_price'] !== '' ? (float) $data['unit_price'] : (float) $product->selling_price;
            Sale::query()->create([
                'reference' => 'NV-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
                'product_id' => $product->id,
                'client_id' => $data['client_id'] ?? null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'unit_cost' => (float) $product->cost + (float) $product->unit_expense,
                'total' => round($unitPrice * $quantity, 2),
                'sold_at' => $data['sold_at'],
                'status' => $data['status'],
            ]);
            $product->decrement('stock', $quantity);
        });

        return redirect()->route('admin.sales.index')->with('success', 'Venta registrada y stock actualizado.');
    }
}
