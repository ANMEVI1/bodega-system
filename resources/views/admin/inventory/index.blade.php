@extends('layouts.admin')
@section('title', 'Inventario')
@section('heading', 'Control de existencias')
@section('content')
    <div data-cinema-enter class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.18em] text-fuchsia-300/90">Almacén y existencias</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-white">
                Control de Inventario <span class="font-mono-num text-fuchsia-300">({{ $products->total() }})</span>
            </h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.export', ['type' => 'products']) }}" class="btn-subtle text-xs">
                <x-icon name="download" class="size-4 text-zinc-400" />
                <span>Exportar inventario (CSV)</span>
            </a>
        </div>
    </div>

    {{-- Stock KPI Cards --}}
    <div data-cinema-enter style="--enter-delay: 1" class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="panel rounded-3xl p-4 border border-white/[.08]">
            <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Unidades en almacén</p>
            <p class="font-mono-num mt-1 text-2xl font-black text-white">{{ number_format($totalUnitsInStock, 0) }}</p>
            <p class="mt-1 text-[11px] text-zinc-500">Total físico disponible</p>
        </div>
        <div class="panel rounded-3xl p-4 border {{ $lowStockCount > 0 ? 'border-amber-400/30 bg-amber-950/10' : 'border-white/[.08]' }}">
            <p class="text-[10px] font-bold uppercase tracking-wider text-amber-300">Stock bajo</p>
            <p class="font-mono-num mt-1 text-2xl font-black text-amber-300">{{ $lowStockCount }}</p>
            <p class="mt-1 text-[11px] text-amber-400/80">Necesitan reposición pronto</p>
        </div>
        <div class="panel rounded-3xl p-4 border {{ $outOfStockCount > 0 ? 'border-rose-500/30 bg-rose-950/10' : 'border-white/[.08]' }}">
            <p class="text-[10px] font-bold uppercase tracking-wider text-rose-300">Agotados (0)</p>
            <p class="font-mono-num mt-1 text-2xl font-black text-rose-300">{{ $outOfStockCount }}</p>
            <p class="mt-1 text-[11px] text-rose-400/80">Sin existencias</p>
        </div>
        <div class="panel rounded-3xl p-4 border border-white/[.08]">
            <p class="text-[10px] font-bold uppercase tracking-wider text-fuchsia-300">Valuación total</p>
            <p class="font-mono-num mt-1 text-2xl font-black text-fuchsia-300">S/ {{ number_format($totalInventoryCost, 2) }}</p>
            <p class="mt-1 text-[11px] text-zinc-500">Capital en bodega al costo</p>
        </div>
    </div>

    {{-- Filters --}}
    <div data-cinema-enter style="--enter-delay: 2" class="mt-5 flex flex-wrap items-center justify-between gap-3">
        <form class="flex flex-1 flex-wrap gap-2.5" method="GET">
            <div class="relative max-w-sm flex-1 min-w-[210px]">
                <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-zinc-500" />
                <input class="field pl-10 text-xs" name="q" value="{{ request('q') }}" placeholder="Buscar por producto o código SKU">
            </div>
            <select class="field max-w-44 text-xs" name="category">
                <option value="">Todas las categorías</option>
                @foreach($categories as $category)
                    <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                @endforeach
            </select>
            <select class="field max-w-44 text-xs" name="stock_status">
                <option value="">Todo el inventario</option>
                <option value="in_stock" @selected(request('stock_status') === 'in_stock')>Nivel óptimo</option>
                <option value="low" @selected(request('stock_status') === 'low')>Stock bajo (alerta)</option>
                <option value="out" @selected(request('stock_status') === 'out')>Agotados (0)</option>
            </select>
            <button class="btn-subtle text-xs">Filtrar</button>
            @if(request()->hasAny(['q', 'category', 'stock_status']))
                <a href="{{ route('admin.inventory.index') }}" class="btn-subtle text-xs text-zinc-400">Limpiar</a>
            @endif
        </form>
    </div>

    @if($lowStockCount)
        <div data-cinema-enter class="mt-4 flex items-center gap-3 rounded-2xl border border-amber-400/20 bg-amber-400/[.07] px-4 py-3 text-xs font-semibold text-amber-100">
            <x-icon name="alert-triangle" class="size-4 shrink-0 text-amber-300" />
            <span>Hay {{ $lowStockCount }} {{ $lowStockCount === 1 ? 'producto que requiere' : 'productos que requieren' }} reposición de stock en bodega.</span>
        </div>
    @endif

    {{-- Stock Table --}}
    <section data-reveal class="panel mt-5 overflow-hidden rounded-3xl">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px] text-left text-xs">
                <thead class="bg-white/[.03] text-[10px] font-extrabold uppercase tracking-wider text-zinc-500">
                    <tr>
                        <th class="px-6 py-4">Producto</th>
                        <th class="px-4 py-4">Unidad</th>
                        <th class="px-4 py-4">Stock actual</th>
                        <th class="px-4 py-4">Alerta mínima</th>
                        <th class="px-4 py-4">Estado del stock</th>
                        <th class="px-6 py-4 text-right">Vendidos</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        @php
                            $isLow = $product->active && (float) $product->stock > 0 && (float) $product->stock <= (float) $product->low_stock_at;
                            $isOut = $product->active && (float) $product->stock <= 0;
                        @endphp
                        <tr class="table-row">
                            <td class="px-6 py-4">
                                <p class="font-bold text-white">{{ $product->name }}</p>
                                <p class="mt-1 flex items-center gap-2 text-[11px] text-zinc-500">
                                    <span class="font-mono-num">{{ $product->sku ?: 'Sin SKU' }}</span>
                                    <span>·</span>
                                    <span class="rounded-md border px-1.5 py-0.5 text-[10px] {{ $product->categoryRelation ? $product->categoryRelation->color_badge_class : 'border-white/10 bg-white/[.05] text-zinc-300' }}">{{ $product->category }}</span>
                                </p>
                            </td>
                            <td class="px-4 py-4 text-zinc-300 capitalize">
                                {{ $product->unit }}
                            </td>
                            <td class="px-4 py-4">
                                <span class="font-mono-num inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-extrabold {{ $isOut ? 'border border-rose-500/30 bg-rose-500/15 text-rose-300' : ($isLow ? 'border border-amber-400/25 bg-amber-400/10 text-amber-200' : 'text-zinc-100') }}">
                                    {{ number_format((float) $product->stock, 2) }}
                                </span>
                            </td>
                            <td class="font-mono-num px-4 py-4 text-zinc-400">
                                {{ number_format((float) $product->low_stock_at, 2) }}
                            </td>
                            <td class="px-4 py-4">
                                @if($isOut)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-500/10 px-2.5 py-1 text-[10px] font-bold text-rose-300 ring-1 ring-rose-500/20">
                                        <span class="size-1.5 rounded-full bg-rose-500"></span>
                                        Agotado
                                    </span>
                                @elseif($isLow)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/10 px-2.5 py-1 text-[10px] font-bold text-amber-300 ring-1 ring-amber-500/20">
                                        <span class="size-1.5 rounded-full bg-amber-500"></span>
                                        Reponer pronto
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-1 text-[10px] font-bold text-emerald-300 ring-1 ring-emerald-500/20">
                                        <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                        Óptimo
                                    </span>
                                @endif
                            </td>
                            <td class="font-mono-num px-6 py-4 text-right text-zinc-300">
                                {{ number_format((float) ($product->units_sold ?? 0), 0) }} {{ $product->unit }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-14 text-center text-zinc-500">
                                No se encontraron registros de existencias en el inventario.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-white/[.06] px-6 py-4">{{ $products->links() }}</div>
    </section>
@endsection
