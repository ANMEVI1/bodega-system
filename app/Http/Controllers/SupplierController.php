<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierPurchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $hasStatus = Schema::hasColumn('suppliers', 'status');
        $hasTaxId = Schema::hasColumn('suppliers', 'tax_id');
        $hasAddress = Schema::hasColumn('suppliers', 'address');
        $hasPaymentTerms = Schema::hasColumn('suppliers', 'payment_terms');

        $search = $request->string('q')->trim()->value();
        $specialty = $request->string('specialty')->trim()->value();
        $status = $request->string('status')->trim()->value();
        $paymentTerms = $request->string('payment_terms')->trim()->value();
        $sort = $request->string('sort')->trim()->value() ?: 'name_asc';

        $query = Supplier::query()
            ->withSum('purchases', 'total')
            ->withSum('purchases', 'quantity')
            ->withCount('purchases')
            ->when($search !== '', function ($q) use ($search, $hasTaxId, $hasAddress): void {
                $q->where(function ($sub) use ($search, $hasTaxId, $hasAddress): void {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('contact_name', 'like', "%{$search}%")
                        ->orWhere('specialty', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");

                    if ($hasTaxId) {
                        $sub->orWhere('tax_id', 'like', "%{$search}%");
                    }
                    if ($hasAddress) {
                        $sub->orWhere('address', 'like', "%{$search}%");
                    }
                });
            })
            ->when($specialty !== '', fn ($q) => $q->where('specialty', $specialty))
            ->when($hasStatus && in_array($status, array_keys(Supplier::STATUSES), true), fn ($q) => $q->where('status', $status))
            ->when($hasPaymentTerms && in_array($paymentTerms, array_keys(Supplier::PAYMENT_TERMS), true), fn ($q) => $q->where('payment_terms', $paymentTerms));

        match ($sort) {
            'spend_desc' => $query->orderByDesc('purchases_sum_total')->orderBy('name'),
            'orders_desc' => $query->orderByDesc('purchases_count')->orderBy('name'),
            'recent' => $query->latest('id'),
            default => $query->orderBy('name'),
        };

        $suppliers = $query->paginate(10)->withQueryString();

        // Estadísticas acumuladas por producto directamente desde supplier_purchases
        // sin consultar ni acoplarse al módulo Productos (en desarrollo por otro integrante).
        $supplierIds = $suppliers->getCollection()->pluck('id');
        $productStatsBySupplier = $this->aggregateProductStats($supplierIds);

        $suppliers->getCollection()->transform(function (Supplier $supplier) use ($productStatsBySupplier): Supplier {
            $stats = $productStatsBySupplier->get($supplier->id, collect());
            $supplierTotal = (float) ($supplier->purchases_sum_total ?? 0);

            $supplier->setAttribute('product_stats', $stats->map(function (object $row) use ($supplierTotal): array {
                $spent = (float) $row->total_spent;

                return [
                    'product_id' => (int) $row->product_id,
                    'product_code' => 'PRD-'.str_pad((string) $row->product_id, 3, '0', STR_PAD_LEFT),
                    'orders_count' => (int) $row->orders_count,
                    'total_quantity' => (float) $row->total_quantity,
                    'avg_unit_cost' => (float) $row->avg_unit_cost,
                    'total_spent' => $spent,
                    'last_purchased_at' => $row->last_purchased_at,
                    'share_percent' => $supplierTotal > 0 ? round(($spent / $supplierTotal) * 100, 1) : 0.0,
                ];
            }));

            return $supplier;
        });

        $portfolioStats = [
            'total_suppliers' => Supplier::query()->count(),
            'active_suppliers' => $hasStatus ? Supplier::query()->where('status', 'active')->count() : Supplier::query()->count(),
            'specialties_count' => Supplier::query()->whereNotNull('specialty')->where('specialty', '!=', '')->distinct('specialty')->count('specialty'),
            'total_invested' => (float) SupplierPurchase::query()->sum('total'),
            'total_orders' => SupplierPurchase::query()->count(),
            'tracked_products' => SupplierPurchase::query()->distinct('product_id')->count('product_id'),
        ];

        $specialties = Supplier::query()
            ->whereNotNull('specialty')
            ->where('specialty', '!=', '')
            ->distinct()
            ->orderBy('specialty')
            ->pluck('specialty');

        return view('admin.suppliers.index', [
            'suppliers' => $suppliers,
            'specialties' => $specialties,
            'portfolioStats' => $portfolioStats,
            'filters' => [
                'q' => $search,
                'specialty' => $specialty,
                'status' => $status,
                'payment_terms' => $paymentTerms,
                'sort' => $sort,
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.suppliers.form', ['supplier' => new Supplier]);
    }

    public function store(Request $request): RedirectResponse
    {
        Supplier::query()->create($this->filteredAttributes($this->validated($request)));

        return redirect()->route('admin.suppliers.index')->with('success', 'Proveedor registrado en la cartera.');
    }

    public function edit(Supplier $supplier): View
    {
        return view('admin.suppliers.form', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->filteredAttributes($this->validated($request)));

        return redirect()->route('admin.suppliers.index')->with('success', 'Información del proveedor actualizada.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->purchases()->exists()) {
            return back()->withErrors([
                'supplier' => 'Este proveedor tiene compras registradas en su historial. Cambia su estado a "En pausa" en lugar de eliminarlo.',
            ]);
        }

        $supplier->delete();

        return redirect()->route('admin.suppliers.index')->with('success', 'Proveedor eliminado de la cartera.');
    }

    private function aggregateProductStats(Collection $supplierIds): Collection
    {
        if ($supplierIds->isEmpty() || ! Schema::hasTable('supplier_purchases')) {
            return collect();
        }

        return DB::table('supplier_purchases')
            ->select([
                'supplier_id',
                'product_id',
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('COALESCE(SUM(quantity), 0) as total_quantity'),
                DB::raw('COALESCE(AVG(unit_cost), 0) as avg_unit_cost'),
                DB::raw('COALESCE(SUM(total), 0) as total_spent'),
                DB::raw('MAX(purchased_at) as last_purchased_at'),
            ])
            ->whereIn('supplier_id', $supplierIds->all())
            ->groupBy('supplier_id', 'product_id')
            ->orderByDesc('total_spent')
            ->get()
            ->groupBy('supplier_id');
    }

    private function filteredAttributes(array $data): array
    {
        $optionalColumns = ['tax_id', 'address', 'payment_terms', 'status', 'whatsapp_template'];

        foreach ($optionalColumns as $column) {
            if (! Schema::hasColumn('suppliers', $column)) {
                unset($data[$column]);
            }
        }

        return $data;
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'tax_id' => ['nullable', 'string', 'max:32'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['required', 'string', 'min:6', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'specialty' => ['nullable', 'string', 'max:160'],
            'payment_terms' => ['nullable', Rule::in(array_keys(Supplier::PAYMENT_TERMS))],
            'status' => ['nullable', Rule::in(array_keys(Supplier::STATUSES))],
            'whatsapp_template' => ['nullable', Rule::in(array_keys(Supplier::WHATSAPP_TEMPLATES))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
