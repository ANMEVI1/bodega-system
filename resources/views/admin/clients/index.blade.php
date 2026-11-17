@extends('layouts.admin')
@section('title', 'Clientes')
@section('heading', 'Cartera de clientes')
@section('content')
    <div data-cinema-enter class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.18em] text-fuchsia-300/90">Relación comercial</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-white">
                Clientes <span class="font-mono-num text-fuchsia-300">({{ $clients->total() }})</span>
            </h1>
        </div>
        <a href="{{ route('admin.clients.create') }}" class="btn-primary text-xs">
            <x-icon name="plus" class="size-4" />
            <span>Agregar cliente</span>
        </a>
    </div>

    <div data-cinema-enter style="--enter-delay: 1" class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <form class="flex flex-1 gap-2.5" method="GET">
            <div class="relative max-w-md flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-zinc-500" />
                <input class="field pl-10 text-xs" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre, teléfono o correo">
            </div>
            <button class="btn-subtle text-xs">Buscar</button>
        </form>
        <a href="{{ route('admin.export', ['type' => 'clients']) }}" class="btn-subtle text-xs">
            <x-icon name="download" class="size-4 text-zinc-400" />
            <span>Excel (CSV)</span>
        </a>
    </div>

    <section data-reveal class="panel mt-5 overflow-hidden rounded-3xl">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[740px] text-left text-xs">
                <thead class="bg-white/[.03] text-[10px] font-extrabold uppercase tracking-wider text-zinc-500">
                    <tr>
                        <th class="px-6 py-4">Cliente</th>
                        <th class="px-4 py-4">WhatsApp</th>
                        <th class="px-4 py-4">Compras acumuladas</th>
                        <th class="px-4 py-4">Estado</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $client)
                        <tr class="table-row">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-fuchsia-500/20 to-violet-600/20 text-xs font-extrabold text-fuchsia-200 ring-1 ring-fuchsia-400/20">
                                        {{ mb_strtoupper(mb_substr($client->name, 0, 1)) }}
                                    </span>
                                    <div>
                                        <p class="font-bold text-white">{{ $client->name }}</p>
                                        <p class="mt-0.5 text-[11px] text-zinc-500">
                                            {{ $client->client_type === 'business' ? 'Empresa o negocio' : 'Persona' }}
                                            · {{ $client->email ?: 'Sin correo registrado' }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <a class="inline-flex items-center gap-1.5 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 font-mono-num text-[11px] font-semibold text-emerald-200 transition hover:bg-emerald-400/20"
                                   target="_blank" rel="noopener"
                                   href="https://wa.me/{{ $client->whatsappPhone() }}?text={{ rawurlencode('Hola '.$client->name.', te escribimos de Bodega Los Olivos. ¿En qué podemos ayudarte hoy?') }}">
                                    <x-icon name="whatsapp" class="size-3.5" />
                                    +{{ $client->whatsappPhone() }}
                                </a>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-mono-num font-extrabold text-white">S/ {{ number_format((float) ($client->total_spent ?? 0), 2) }}</p>
                                <p class="mt-0.5 text-[11px] text-zinc-500">{{ $client->latestSale?->product?->name ?? 'Sin compras aún' }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold {{ $client->status === 'active' ? 'bg-emerald-400/10 text-emerald-200 ring-1 ring-emerald-400/20' : ($client->status === 'follow-up' ? 'bg-amber-400/10 text-amber-200 ring-1 ring-amber-400/20' : 'bg-white/[.05] text-zinc-400') }}">
                                    <span class="size-1.5 rounded-full {{ $client->status === 'active' ? 'bg-emerald-400' : ($client->status === 'follow-up' ? 'bg-amber-400' : 'bg-zinc-500') }}"></span>
                                    {{ ['active' => 'Activo', 'follow-up' => 'Seguimiento', 'inactive' => 'Inactivo'][$client->status] ?? $client->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.clients.show', $client) }}" class="btn-subtle px-3 py-1.5 text-[11px]">
                                        Ver ficha
                                    </a>
                                    <a href="{{ route('admin.clients.edit', $client) }}" class="btn-subtle px-3 py-1.5 text-[11px]">
                                        <x-icon name="edit" class="size-3.5" />
                                        Editar
                                    </a>
                                    <form method="POST" action="{{ route('admin.clients.destroy', $client) }}"
                                          data-confirm="¿Seguro que deseas eliminar a «{{ $client->name }}»? Las ventas se conservarán, pero quedarán sin cliente asociado.">
                                        @csrf @method('DELETE')
                                        <button class="inline-flex items-center gap-1 rounded-xl border border-rose-400/15 bg-rose-500/[.06] px-3 py-1.5 text-[11px] font-bold text-rose-200 transition hover:bg-rose-500/15">
                                            <x-icon name="trash" class="size-3.5" />
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-14 text-center text-zinc-500">
                                No encontramos clientes con esos datos. Agrega uno para empezar tu cartera.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-white/[.06] px-6 py-4">{{ $clients->links() }}</div>
    </section>
@endsection
