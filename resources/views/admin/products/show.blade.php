@extends('layouts.admin')
@section('title', $product->name . ' · Ficha de producto')
@section('heading', 'Detalle de producto')
@section('content')
    <div class="space-y-6" data-cinema-enter>
        {{-- Header & Actions --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.products.index') }}" class="btn-subtle size-9 p-0 inline-flex items-center justify-center rounded-xl" title="Volver a productos">
                    <x-icon name="arrow-left" class="size-4" />
                </a>
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-2xl font-extrabold tracking-tight text-white">{{ $product->name }}</h1>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $product->active ? 'bg-emerald-400/10 text-emerald-200 ring-1 ring-emerald-400/20' : 'bg-white/[.05] text-zinc-400' }}">
                            <span class="size-1.5 rounded-full {{ $product->active ? 'bg-emerald-400' : 'bg-zinc-500' }}"></span>
                            {{ $product->active ? 'Activo' : 'Inactivo' }}
                        </span>
                        @if($product->is_public)
                            <span class="rounded-full bg-sky-400/10 px-2 py-0.5 text-[10px] font-semibold text-sky-300 ring-1 ring-sky-400/20">Público en web</span>
                        @else
                            <span class="rounded-full bg-zinc-800/80 px-2 py-0.5 text-[10px] font-semibold text-zinc-400 ring-1 ring-white/10">Solo administrador</span>
                        @endif
                    </div>
                    <p class="mt-0.5 flex items-center gap-2 text-xs text-zinc-400">
                        <span class="font-mono-num">{{ $product->sku ?: 'Sin SKU asignado' }}</span>
                        <span>·</span>
                        <span class="inline-flex items-center gap-1 font-semibold text-fuchsia-300">
                            <x-icon name="tag" class="size-3" />
                            {{ $product->category }}
                        </span>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.products.edit', $product) }}" class="btn-primary text-xs">
                    <x-icon name="edit" class="size-4" />
                    <span>Editar producto</span>
                </a>
                <form method="POST" action="{{ route('admin.products.destroy', $product) }}"
                      data-confirm="¿Seguro que deseas eliminar «{{ $product->name }}»?">
                    @csrf @method('DELETE')
                    <button class="inline-flex items-center gap-1.5 rounded-2xl border border-rose-400/20 bg-rose-500/[.08] px-3.5 py-2.5 text-xs font-bold text-rose-200 transition hover:bg-rose-500/20">
                        <x-icon name="trash" class="size-4" />
                        <span>Eliminar</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- Top KPI Cards --}}
        @php
            $isLow = $product->active && (float) $product->stock > 0 && (float) $product->stock <= (float) $product->low_stock_at;
            $isOut = $product->active && (float) $product->stock <= 0;
        @endphp
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Stock Card --}}
            <div class="panel rounded-3xl p-5 border {{ $isOut ? 'border-rose-500/30 bg-rose-950/10' : ($isLow ? 'border-amber-400/30 bg-amber-950/10' : 'border-white/[.08]') }}">
                <div class="flex items-center justify-between">
                    <p class="text-[10px] font-extrabold uppercase tracking-[.18em] text-zinc-400">Stock disponible</p>
                    <span class="rounded-lg p-1.5 {{ $isOut ? 'bg-rose-500/20 text-rose-300' : ($isLow ? 'bg-amber-500/20 text-amber-300' : 'bg-emerald-500/20 text-emerald-300') }}">
                        <x-icon name="package" class="size-4" />
                    </span>
                </div>
                <p class="font-mono-num mt-2 text-3xl font-black text-white">
                    {{ number_format((float) $product->stock, 2) }}
                    <span class="text-xs font-bold text-zinc-400">{{ $product->unit }}</span>
                </p>
                <p class="mt-2 text-[11px] font-semibold {{ $isOut ? 'text-rose-400' : ($isLow ? 'text-amber-400' : 'text-emerald-400') }}">
                    @if($isOut)
                        Agotado · Se requiere abastecimiento
                    @elseif($isLow)
                        Stock bajo · Mínimo sugerido: {{ number_format((float) $product->low_stock_at, 0) }} {{ $product->unit }}
                    @else
                        Nivel de inventario óptimo
                    @endif
                </p>
            </div>

            {{-- Selling Price Card --}}
            <div class="panel rounded-3xl p-5 border border-white/[.08]">
                <div class="flex items-center justify-between">
                    <p class="text-[10px] font-extrabold uppercase tracking-[.18em] text-zinc-400">Precio de venta</p>
                    <span class="rounded-lg bg-emerald-500/20 p-1.5 text-emerald-300">
                        <x-icon name="shopping-bag" class="size-4" />
                    </span>
                </div>
                <p class="font-mono-num mt-2 text-3xl font-black text-emerald-300">
                    S/ {{ number_format((float) $product->selling_price, 2) }}
                </p>
                <p class="mt-2 text-[11px] text-zinc-400">
                    Precio por {{ $product->unit }} para el cliente
                </p>
            </div>

            {{-- Unit Cost Card --}}
            <div class="panel rounded-3xl p-5 border border-white/[.08]">
                <div class="flex items-center justify-between">
                    <p class="text-[10px] font-extrabold uppercase tracking-[.18em] text-zinc-400">Costo unitario total</p>
                    <span class="rounded-lg bg-purple-500/20 p-1.5 text-purple-300">
                        <x-icon name="wallet" class="size-4" />
                    </span>
                </div>
                <p class="font-mono-num mt-2 text-3xl font-black text-zinc-200">
                    S/ {{ number_format($unitCostTotal, 2) }}
                </p>
                <p class="mt-2 font-mono-num text-[11px] text-zinc-400">
                    Compra S/ {{ number_format((float) $product->cost, 2) }} + Gasto S/ {{ number_format((float) $product->unit_expense, 2) }}
                </p>
            </div>

            {{-- Margin & Profit Card --}}
            <div class="panel rounded-3xl p-5 border border-white/[.08]">
                <div class="flex items-center justify-between">
                    <p class="text-[10px] font-extrabold uppercase tracking-[.18em] text-fuchsia-300">Ganancia neta / u</p>
                    <span class="rounded-lg bg-fuchsia-500/20 p-1.5 text-fuchsia-300">
                        <x-icon name="trending-up" class="size-4" />
                    </span>
                </div>
                <p class="font-mono-num mt-2 text-3xl font-black text-fuchsia-300">
                    S/ {{ number_format($unitProfit, 2) }}
                </p>
                <p class="mt-2 text-[11px] font-bold text-fuchsia-200">
                    Margen: <span class="font-mono-num">{{ $marginPercent }}%</span> sobre venta
                </p>
            </div>
        </div>

        {{-- Main Detail Grids --}}
        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Technical Sheet --}}
            <div class="panel space-y-5 rounded-3xl p-6 lg:col-span-2">
                <div>
                    <p class="text-[10px] font-extrabold uppercase tracking-[.18em] text-fuchsia-300">Ficha técnica</p>
                    <h2 class="mt-1 text-lg font-bold text-white">Especificaciones y parámetros</h2>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-white/[.06] bg-white/[.02] p-4">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Categoría asignada</p>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-xs font-bold {{ $product->categoryRelation ? $product->categoryRelation->color_badge_class : 'border-fuchsia-500/20 bg-fuchsia-500/10 text-fuchsia-300' }}">
                                <x-icon name="tag" class="size-3" />
                                {{ $product->category }}
                            </span>
                            @if($product->categoryRelation)
                                <a href="{{ route('admin.categories.edit', $product->categoryRelation) }}" class="text-[11px] text-zinc-400 hover:text-white" title="Editar categoría">
                                    <x-icon name="edit" class="size-3" />
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/[.06] bg-white/[.02] p-4">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Unidad de medida</p>
                        <p class="mt-2 text-sm font-bold text-white capitalize">{{ $product->unit }}</p>
                    </div>

                    <div class="rounded-2xl border border-white/[.06] bg-white/[.02] p-4">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Umbral alerta stock bajo</p>
                        <p class="font-mono-num mt-2 text-sm font-bold text-amber-300">
                            {{ number_format((float) $product->low_stock_at, 2) }} {{ $product->unit }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/[.06] bg-white/[.02] p-4">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Ámbito de visibilidad</p>
                        <p class="mt-2 text-xs font-semibold {{ $product->is_public ? 'text-sky-300' : 'text-zinc-300' }}">
                            {{ $product->is_public ? 'Visible en catálogo público' : 'Exclusivo del panel administrativo (no visible en web)' }}
                        </p>
                    </div>
                </div>

                {{-- Barcode Simulator Display --}}
                <div class="rounded-2xl border border-white/[.08] bg-black/40 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Identificador / Código de barras</p>
                            <p class="font-mono-num text-sm font-extrabold text-white mt-0.5">{{ $product->sku ?: 'SIN-CODIGO-SKU' }}</p>
                        </div>
                        <span class="text-xs text-zinc-500">Escaneable en caja</span>
                    </div>

                    {{-- Visual Barcode stripes --}}
                    <div class="mt-4 flex h-12 items-end justify-center gap-1 rounded-xl bg-white p-2">
                        @for($i = 0; $i < 32; $i++)
                            @php
                                $w = in_array($i, [1, 2, 6, 7, 12, 13, 19, 24, 28, 29]) ? 'w-1' : (in_array($i, [4, 9, 15, 21, 26]) ? 'w-1.5' : 'w-0.5');
                                $h = ($i % 5 === 0) ? 'h-full' : 'h-5/6';
                            @endphp
                            <span class="{{ $w }} {{ $h }} bg-black"></span>
                        @endfor
                    </div>
                    <p class="font-mono-num mt-1 text-center text-[10px] tracking-widest text-zinc-400">{{ $product->sku ?: '000000000000' }}</p>
                </div>
            </div>

            {{-- Performance & Sales Summary --}}
            <div class="panel space-y-5 rounded-3xl p-6">
                <div>
                    <p class="text-[10px] font-extrabold uppercase tracking-[.18em] text-fuchsia-300">Rendimiento comercial</p>
                    <h2 class="mt-1 text-lg font-bold text-white">Ventas acumuladas</h2>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between border-b border-white/[.06] pb-3 text-xs">
                        <span class="text-zinc-400">Unidades vendidas:</span>
                        <span class="font-mono-num font-extrabold text-white">{{ number_format($unitsSold, 0) }} {{ $product->unit }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-white/[.06] pb-3 text-xs">
                        <span class="text-zinc-400">Total recaudado:</span>
                        <span class="font-mono-num font-extrabold text-emerald-300">S/ {{ number_format($totalRevenue, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-white/[.06] pb-3 text-xs">
                        <span class="text-zinc-400">Utilidad bruta generada:</span>
                        <span class="font-mono-num font-extrabold text-fuchsia-300">S/ {{ number_format($totalGrossProfit, 2) }}</span>
                    </div>
                </div>

                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 mb-2">Últimas salidas / ventas</p>
                    @if($recentSales->isNotEmpty())
                        <div class="space-y-2">
                            @foreach($recentSales as $sale)
                                <div class="flex items-center justify-between rounded-xl bg-white/[.02] p-2.5 text-[11px]">
                                    <div>
                                        <p class="font-mono-num font-bold text-white">{{ $sale->ticket_code }}</p>
                                        <p class="text-[10px] text-zinc-500">{{ $sale->sold_at?->format('d/m/Y') }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-mono-num font-bold text-zinc-200">{{ number_format((float) $sale->quantity, 0) }} {{ $product->unit }}</p>
                                        <p class="font-mono-num text-[10px] text-emerald-400">S/ {{ number_format((float) $sale->total, 2) }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="rounded-xl border border-white/[.04] bg-white/[.01] p-4 text-center text-xs text-zinc-500">
                            Aún no se han registrado ventas para este producto.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
