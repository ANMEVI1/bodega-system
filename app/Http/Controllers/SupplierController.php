<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $suppliers = Supplier::query()->withSum('purchases', 'total')
            ->with(['purchases' => fn ($query) => $query->with('product')->latest('purchased_at')->limit(3)])
            ->when($request->string('q')->trim()->value(), fn ($query, $term) => $query->where(fn ($query) => $query->where('name', 'like', "%{$term}%")->orWhere('specialty', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%")))
            ->orderBy('name')->paginate(12)->withQueryString();

        return view('admin.suppliers.index', ['suppliers' => $suppliers, 'products' => Product::query()->where('active', true)->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('admin.suppliers.form', ['supplier' => new Supplier]);
    }

    public function store(Request $request): RedirectResponse
    {
        Supplier::query()->create($this->validated($request));

        return redirect()->route('admin.suppliers.index')->with('success', 'Proveedor agregado.');
    }

    public function edit(Supplier $supplier): View
    {
        return view('admin.suppliers.form', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validated($request));

        return redirect()->route('admin.suppliers.index')->with('success', 'Proveedor actualizado.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->purchases()->exists()) {
            return back()->withErrors(['supplier' => 'Este proveedor tiene compras registradas. Conserva su historial y edita los datos en su lugar.']);
        }
        $supplier->delete();

        return redirect()->route('admin.suppliers.index')->with('success', 'Proveedor eliminado.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'specialty' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
