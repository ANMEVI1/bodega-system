<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Support\InputGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $clients = Client::query()->with(['latestSale.product'])
            ->withSum(['sales as total_spent' => fn ($query) => $query->where('status', 'paid')], 'total')
            ->when($request->string('q')->trim()->value(), function ($query, $term) {
                $escaped = InputGuard::escapeLike($term);
                $query->where(fn ($q) => $q->where('name', 'like', "%{$escaped}%")
                    ->orWhere('phone', 'like', "%{$escaped}%")
                    ->orWhere('email', 'like', "%{$escaped}%"));
            })
            ->orderBy('name')->paginate(12)->withQueryString();

        return view('admin.clients.index', ['clients' => $clients]);
    }

    public function show(Client $client): View
    {
        $sales = $client->sales()->with('product')->latest('sold_at')->latest()->paginate(8);
        $products = $client->sales()
            ->join('products', 'sales.product_id', '=', 'products.id')
            ->where('sales.status', 'paid')
            ->select(['products.id', 'products.name', 'products.unit'])
            ->selectRaw('SUM(sales.quantity) as total_quantity')
            ->selectRaw('SUM(sales.total) as total_spent')
            ->selectRaw('MAX(sales.sold_at) as last_purchased_at')
            ->groupBy('products.id', 'products.name', 'products.unit')
            ->orderByDesc('total_quantity')
            ->get();
        $lastPurchase = $client->sales()->where('status', 'paid')->with('product')->latest('sold_at')->latest()->first();

        return view('admin.clients.show', [
            'client' => $client,
            'sales' => $sales,
            'products' => $products,
            'paidSalesCount' => $client->sales()->where('status', 'paid')->count(),
            'totalSpent' => $client->sales()->where('status', 'paid')->sum('total'),
            'lastPurchase' => $lastPurchase,
        ]);
    }

    public function create(): View
    {
        return view('admin.clients.form', ['client' => new Client]);
    }

    public function store(Request $request): RedirectResponse
    {
        Client::query()->create($this->validated($request));

        return redirect()->route('admin.clients.index')->with('success', 'Cliente agregado.');
    }

    public function edit(Client $client): View
    {
        return view('admin.clients.form', compact('client'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $client->update($this->validated($request));

        return redirect()->route('admin.clients.index')->with('success', 'Cliente actualizado.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();

        return redirect()->route('admin.clients.index')->with('success', 'Cliente eliminado.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'client_type' => ['required', 'in:person,business'],
            'document_type' => ['required', 'in:dni,ruc,ce,passport'],
            'document_number' => ['required', 'string', 'max:30'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:15', 'regex:/\A[0-9]+\z/'],
            'email' => ['nullable', 'required_if:preferred_contact,email', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'preferred_contact' => ['required', 'in:whatsapp,phone,email'],
            'source' => ['nullable', 'in:walk_in,referral,social,whatsapp,other'],
            'status' => ['required', 'in:active,follow-up,inactive'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $phone = $data['phone'];
        $peruvianMobile = strlen($phone) === 9 && str_starts_with($phone, '9');

        if ($peruvianMobile) {
            $phone = '51'.$phone;
        } elseif (! preg_match('/\A[1-9][0-9]{9,14}\z/', $phone)) {
            throw ValidationException::withMessages([
                'phone' => 'Ingresa un celular peruano de 9 dígitos o incluye el código de país.',
            ]);
        }

        $documentNumber = $data['document_number'] ?? null;
        $documentType = $data['document_type'] ?? null;
        $allowedDocumentTypes = $data['client_type'] === 'business'
            ? ['ruc']
            : ['dni', 'ce', 'passport'];

        if ($documentType && ! in_array($documentType, $allowedDocumentTypes, true)) {
            throw ValidationException::withMessages([
                'document_type' => 'El tipo de documento no corresponde al tipo de cliente seleccionado.',
            ]);
        }

        if ($documentNumber !== null && $documentNumber !== '') {
            $validDocumentNumber = match ($documentType) {
                'dni' => preg_match('/\A[0-9]{8}\z/', $documentNumber),
                'ruc' => preg_match('/\A[0-9]{11}\z/', $documentNumber),
                'ce', 'passport' => preg_match('/\A[A-Za-z0-9]{1,30}\z/', $documentNumber),
                default => false,
            };

            if (! $validDocumentNumber) {
                $message = match ($documentType) {
                    'dni' => 'El DNI debe contener exactamente 8 números.',
                    'ruc' => 'El RUC debe contener exactamente 11 números.',
                    'ce', 'passport' => 'El documento solo puede contener letras y números.',
                    default => 'Selecciona el tipo de documento correspondiente.',
                };

                throw ValidationException::withMessages(['document_number' => $message]);
            }
        }

        if ($data['client_type'] === 'person') {
            $data['contact_name'] = null;
        }

        $data['phone'] = $phone;

        return $data;
    }
}
