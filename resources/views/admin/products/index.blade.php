@extends('layouts.admin')
@section('title', 'Inventario')
@section('heading', 'Inventario de productos')
@section('content')
    <div data-cinema-enter class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.18em] text-fuchsia-300/90">Catálogo y márgenes</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-white">
                Inventario <span class="font-mono-num text-fuchsia-300">({{ $products->total() }})</span>
            </h1>
        </div>
        <a href="{{ route('admin.products.create') }}" class="btn-primary text-xs">
            <x-icon name="plus" class="size-4" />
            <span>Agregar producto</span>
        </a>
    </div>

    <div data-cinema-enter style="--enter-delay: 1" class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <form class="flex flex-1 flex-wrap gap-2.5" method="GET">
            <div class="relative max-w-sm flex-1 min-w-[210px]">
                <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-zinc-500" />
                <input class="field pl-10 text-xs" name="q" value="{{ request('q') }}" placeholder="Buscar producto, SKU o categoría">
            </div>
            <select class="field max-w-52 text-xs" name="category">
                <option value="">Todas las categorías</option>
                @foreach($categories as $category)
                    <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                @endforeach
            </select>
            <button class="btn-subtle text-xs">Filtrar</button>
        </form>
        <a href="{{ route('admin.export', ['type' => 'products']) }}" class="btn-subtle text-xs">
            <x-icon name="download" class="size-4 text-zinc-400" />
            <span>Excel (CSV)</span>
        </a>
    </div>

    @if($lowStockCount)
        <div data-cinema-enter style="--enter-delay: 2" class="mt-4 flex items-center gap-3 rounded-2xl border border-amber-400/20 bg-amber-400/[.07] px-4 py-3 text-xs font-semibold text-amber-100">
            <x-icon name="alert-triangle" class="size-4 shrink-0 text-amber-300" />
            <span>{{ $lowStockCount }} {{ $lowStockCount === 1 ? 'producto necesita' : 'productos necesitan' }} reposición pronto.</span>
        </div>
    @endif

    <section data-reveal class="panel mt-5 overflow-hidden rounded-3xl">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[880px] text-left text-xs">
                <thead class="bg-white/[.03] text-[10px] font-extrabold uppercase tracking-wider text-zinc-500">
                    <tr>
                        <th class="px-6 py-4">Producto</th>
                        <th class="px-4 py-4">Costo / precio</th>
                        <th class="px-4 py-4">Stock</th>
                        <th class="px-4 py-4">Vendidos</th>
                        <th class="px-4 py-4">Estado</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        @php
                            $low = $product->active && (float) $product->stock <= (float) $product->low_stock_at;
                        @endphp
                        <tr class="table-row">
                            <td class="px-6 py-4">
                                <p class="font-bold text-white">{{ $product->name }}</p>
                                <p class="mt-1 flex items-center gap-2 text-[11px] text-zinc-500">
                                    <span class="font-mono-num">{{ $product->sku ?: 'Sin SKU' }}</span>
                                    <span>·</span>
                                    <span class="rounded-md bg-white/[.05] px-1.5 py-0.5 text-[10px] text-zinc-300">{{ $product->category }}</span>
                                </p>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-mono-num text-[11px] text-zinc-400">
                                    Costo S/ {{ number_format((float) $product->cost, 2) }} + S/ {{ number_format((float) $product->unit_expense, 2) }} gasto/u
                                </p>
                                <p class="font-mono-num mt-1 text-sm font-extrabold text-emerald-300">
                                    Precio S/ {{ number_format((float) $product->selling_price, 2) }}
                                </p>
                            </td>
                            <td class="px-4 py-4">
                                <span class="font-mono-num inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-extrabold {{ $low ? 'border border-amber-400/25 bg-amber-400/10 text-amber-200' : 'text-zinc-100' }}">
                                    {{ number_format((float) $product->stock, 0) }} {{ $product->unit }}
                                </span>
                                @if($low)
                                    <span class="mt-1 block text-[10px] font-semibold text-amber-300">Reponer pronto</span>
                                @endif
                            </td>
                            <td class="font-mono-num px-4 py-4 text-zinc-300">
                                {{ number_format((float) ($product->units_sold ?? 0), 0) }}
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold {{ $product->active ? 'bg-emerald-400/10 text-emerald-200 ring-1 ring-emerald-400/20' : 'bg-white/[.05] text-zinc-400' }}">
                                    <span class="size-1.5 rounded-full {{ $product->active ? 'bg-emerald-400' : 'bg-zinc-500' }}"></span>
                                    {{ $product->active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.products.edit', $product) }}" class="btn-subtle px-3 py-1.5 text-[11px]">
                                        <x-icon name="edit" class="size-3.5" />
                                        Editar
                                    </a>
                                    <form method="POST" action="{{ route('admin.products.destroy', $product) }}"
                                          data-confirm="¿Seguro que deseas eliminar «{{ $product->name }}» del inventario?">
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
                            <td colspan="6" class="px-6 py-14 text-center text-zinc-500">
                                Tu inventario está vacío. Agrega productos para controlar el stock y registrar ventas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-white/[.06] px-6 py-4">{{ $products->links() }}</div>
    </section>
@endsection
