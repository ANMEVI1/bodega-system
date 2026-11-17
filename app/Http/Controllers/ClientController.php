<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $clients = Client::query()->with(['latestSale.product'])->withSum('sales as total_spent', 'total')
            ->when($request->string('q')->trim()->value(), fn ($query, $term) => $query->where(fn ($query) => $query->where('name', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")))
            ->orderBy('name')->paginate(12)->withQueryString();

        return view('admin.clients.index', ['clients' => $clients]);
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
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['required', 'in:active,follow-up,inactive'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
