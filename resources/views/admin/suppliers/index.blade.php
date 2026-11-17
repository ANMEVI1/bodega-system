@extends('layouts.admin')
@section('title', 'Proveedores')
@section('heading', 'Cartera de proveedores')
@section('content')
    <div data-cinema-enter class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.18em] text-cyan-300/90">Red de abastecimiento</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-white">
                Proveedores <span class="font-mono-num text-fuchsia-300">({{ $suppliers->total() }})</span>
            </h1>
        </div>
        <a href="{{ route('admin.suppliers.create') }}" class="btn-primary text-xs">
            <x-icon name="plus" class="size-4" />
            <span>Agregar proveedor</span>
        </a>
    </div>

    <form data-cinema-enter style="--enter-delay: 1" class="mt-6 flex flex-wrap gap-2.5" method="GET">
        <div class="relative max-w-md flex-1 min-w-[220px]">
            <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-zinc-500" />
            <input class="field pl-10 text-xs" name="q" value="{{ request('q') }}" placeholder="Buscar por proveedor, rubro o teléfono">
        </div>
        <button class="btn-subtle text-xs">Buscar</button>
    </form>

    <div class="mt-5 grid gap-5 xl:grid-cols-2">
        @forelse($suppliers as $supplier)
            <article data-reveal style="--stagger: {{ $loop->index % 2 }}" class="panel spotlight-card rounded-3xl p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex items-center gap-3.5">
                        <span class="grid size-12 shrink-0 place-items-center rounded-2xl border border-cyan-400/20 bg-cyan-400/10 text-cyan-300">
                            <x-icon name="truck" class="size-5" />
                        </span>
                        <div>
                            <h2 class="text-base font-extrabold text-white">{{ $supplier->name }}</h2>
                            <p class="mt-1 text-[11px] text-zinc-400">
                                {{ $supplier->specialty ?: 'Proveedor general' }}
                                @if($supplier->contact_name) · {{ $supplier->contact_name }} @endif
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a target="_blank" rel="noopener"
                           href="https://wa.me/{{ preg_replace('/\D+/', '', $supplier->phone) }}?text={{ urlencode('Hola '.$supplier->name.', te escribo de Bodega Los Olivos para consultar disponibilidad.') }}"
                           class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-400/20 bg-emerald-400/10 px-3 py-1.5 text-[11px] font-bold text-emerald-200 transition hover:bg-emerald-400/20">
                            <x-icon name="whatsapp" class="size-3.5" />
                            WhatsApp
                        </a>
                        <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="btn-subtle px-3 py-1.5 text-[11px]">
                            <x-icon name="edit" class="size-3.5" />
                            Editar
                        </a>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap items-center gap-4 border-y border-white/[.06] py-3 text-xs">
                    <a class="inline-flex items-center gap-1.5 font-mono-num text-zinc-300 transition hover:text-white" href="tel:{{ $supplier->phone }}">
                        <x-icon name="phone" class="size-3.5 text-zinc-500" />
                        {{ $supplier->phone }}
                    </a>
                    @if($supplier->email)
                        <a class="inline-flex items-center gap-1.5 text-zinc-400 transition hover:text-white" href="mailto:{{ $supplier->email }}">
                            <x-icon name="mail" class="size-3.5 text-zinc-500" />
                            {{ $supplier->email }}
                        </a>
                    @endif
                    <span class="font-mono-num ml-auto font-extrabold text-white">
                        Compras: S/ {{ number_format((float) ($supplier->purchases_sum_total ?? 0), 2) }}
                    </span>
                </div>

                @if($supplier->purchases->isNotEmpty())
                    <div class="mt-4">
                        <p class="mb-2 text-[10px] font-extrabold uppercase tracking-wider text-zinc-500">Últimas compras</p>
                        <div class="space-y-1.5">
                            @foreach($supplier->purchases as $purchase)
                                <div class="flex items-center justify-between rounded-xl bg-white/[.02] px-3 py-2 text-[11px]">
                                    <span class="text-zinc-300">{{ $purchase->product->name }} · {{ number_format((float) $purchase->quantity, 0) }} u · <span class="text-zinc-500">{{ $purchase->purchased_at->format('d/m/Y') }}</span></span>
                                    <span class="font-mono-num font-bold text-white">S/ {{ number_format((float) $purchase->total, 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($products->isNotEmpty())
                    <details class="group mt-4 rounded-2xl border border-white/[.08] bg-white/[.025]">
                        <summary class="flex cursor-pointer list-none items-center justify-between px-4 py-3 text-xs font-bold text-fuchsia-200 transition hover:text-fuchsia-100">
                            <span class="inline-flex items-center gap-2">
                                <x-icon name="plus" class="size-4 text-fuchsia-300" />
                                Registrar compra y aumentar inventario
                            </span>
                            <x-icon name="chevron-down" class="size-4 text-zinc-400 transition group-open:rotate-180" />
                        </summary>
                        <form method="POST" action="{{ route('admin.purchases.store') }}" class="grid gap-3 border-t border-white/[.06] p-4 sm:grid-cols-2">
                            @csrf
                            <input type="hidden" name="supplier_id" value="{{ $supplier->id }}">
                            <label class="text-[11px] font-bold text-zinc-400 sm:col-span-2">
                                Producto
                                <select class="field mt-1.5 text-xs" name="product_id" required>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="text-[11px] font-bold text-zinc-400">
                                Cantidad
                                <input class="field font-mono-num mt-1.5 text-xs" type="number" step="0.01" min="0.01" name="quantity" required>
                            </label>
                            <label class="text-[11px] font-bold text-zinc-400">
                                Costo unitario (S/)
                                <input class="field font-mono-num mt-1.5 text-xs" type="number" step="0.01" min="0" name="unit_cost" required>
                            </label>
                            <label class="text-[11px] font-bold text-zinc-400">
                                Fecha
                                <input class="field mt-1.5 text-xs" type="date" name="purchased_at" value="{{ now()->toDateString() }}" required>
                            </label>
                            <label class="text-[11px] font-bold text-zinc-400">
                                Referencia
                                <input class="field font-mono-num mt-1.5 text-xs" name="reference" maxlength="100" placeholder="FAC-001">
                            </label>
                            <button class="btn-primary text-xs sm:col-span-2">Guardar compra e inventario</button>
                        </form>
                    </details>
                @endif

                <div class="mt-4 flex justify-end">
                    <form method="POST" action="{{ route('admin.suppliers.destroy', $supplier) }}"
                          data-confirm="¿Seguro que deseas eliminar al proveedor «{{ $supplier->name }}»?">
                        @csrf @method('DELETE')
                        <button class="inline-flex items-center gap-1 rounded-xl px-2.5 py-1.5 text-[11px] font-bold text-rose-300/80 transition hover:bg-rose-500/10 hover:text-rose-200">
                            <x-icon name="trash" class="size-3.5" />
                            Eliminar proveedor
                        </button>
                    </form>
                </div>
            </article>
        @empty
            <div class="panel rounded-3xl p-12 text-center text-sm text-zinc-500 xl:col-span-2">
                Aún no registras proveedores. Agrega el primero para ordenar tus compras y reabastecer stock.
            </div>
        @endforelse
    </div>
    <div class="mt-6">{{ $suppliers->links() }}</div>
@endsection
