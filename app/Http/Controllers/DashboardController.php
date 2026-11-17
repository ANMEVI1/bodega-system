<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ContactSubmission;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\SupplierPurchase;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $sales = Sale::query()->where('status', 'paid')->whereDate('sold_at', '>=', now()->startOfMonth());

        return view('admin.dashboard', [
            'clientsCount' => Client::query()->count(),
            'productsCount' => Product::query()->where('active', true)->count(),
            'suppliersCount' => Supplier::query()->count(),
            'lowStockCount' => Product::query()->where('active', true)->whereColumn('stock', '<=', 'low_stock_at')->count(),
            'lowStockProducts' => Product::query()->where('active', true)->whereColumn('stock', '<=', 'low_stock_at')->orderBy('stock')->limit(4)->get(),
            'monthRevenue' => (clone $sales)->sum('total'),
            'monthProfit' => (clone $sales)->selectRaw('COALESCE(SUM(total - quantity * unit_cost), 0) as profit')->value('profit'),
            'recentSales' => Sale::query()->with(['product', 'client'])->latest('sold_at')->latest()->limit(6)->get(),
            'topProducts' => Product::query()->withSum(['sales as revenue' => fn ($query) => $query->where('status', 'paid')], 'total')
                ->withSum(['sales as units_sold' => fn ($query) => $query->where('status', 'paid')], 'quantity')
                ->orderByDesc('units_sold')->limit(5)->get(),
            'topClients' => Client::query()->withSum(['sales as revenue' => fn ($query) => $query->where('status', 'paid')], 'total')
                ->whereHas('sales', fn ($query) => $query->where('status', 'paid'))
                ->orderByDesc('revenue')->limit(3)->get(),
            'monthlySales' => collect(range(5, 0))->map(function (int $monthsAgo): array {
                $month = Carbon::now()->locale('es')->startOfMonth()->subMonths($monthsAgo);
                $total = Sale::query()->where('status', 'paid')->whereBetween('sold_at', [$month, $month->copy()->endOfMonth()])->sum('total');

                return [
                    'label' => ucfirst(rtrim($month->translatedFormat('M'), '.')),
                    'fullLabel' => ucfirst($month->translatedFormat('F Y')),
                    'total' => (float) $total,
                ];
            }),
            'recentContacts' => ContactSubmission::query()->where('status', 'new')->latest()->limit(3)->get(),
            'monthPurchases' => SupplierPurchase::query()->whereDate('purchased_at', '>=', now()->startOfMonth())->sum('total'),
        ]);
    }
}
