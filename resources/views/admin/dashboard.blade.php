@extends('layouts.admin')
@section('title', 'Resumen')
@section('heading', 'Tu negocio hoy')
@section('content')
    @php
        $maxSales = max(1, (float) $monthlySales->max('total'));
        $adminFirstName = explode(' ', trim(auth()->user()?->name ?? 'Administrador'))[0];
        $newContactsTotal = $navNewContacts ?? $recentContacts->count();
        $kpiCards = [
            [
                'label' => 'Ventas del mes',
                'raw' => (float) $monthRevenue,
                'prefix' => 'S/ ',
                'decimals' => 2,
                'hint' => 'Ingresos registrados (pagados)',
                'icon' => 'trending-up',
                'badgeTone' => 'text-emerald-300 bg-emerald-400/10 border-emerald-400/20',
            ],
            [
                'label' => 'Utilidad de productos',
                'raw' => (float) $monthProfit,
                'prefix' => 'S/ ',
                'decimals' => 2,
                'hint' => 'Ventas menos costo y gastos unitarios',
                'icon' => 'wallet',
                'badgeTone' => 'text-violet-300 bg-violet-400/10 border-violet-400/20',
            ],
            [
                'label' => 'Productos activos',
                'raw' => (float) $productsCount,
                'prefix' => '',
                'decimals' => 0,
                'hint' => $lowStockCount . ' con stock bajo',
                'icon' => 'package',
                'badgeTone' => $lowStockCount
                    ? 'text-amber-300 bg-amber-400/10 border-amber-400/20'
                    : 'text-cyan-300 bg-cyan-400/10 border-cyan-400/20',
            ],
            [
                'label' => 'Clientes registrados',
                'raw' => (float) $clientsCount,
                'prefix' => '',
                'decimals' => 0,
                'hint' => $suppliersCount . ' proveedores activos',
                'icon' => 'users',
                'badgeTone' => 'text-fuchsia-300 bg-fuchsia-400/10 border-fuchsia-400/20',
            ],
        ];
    @endphp

    <div data-cinema-enter class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.18em] text-fuchsia-300/90">Centro de operaciones</p>
            <h1 class="mt-1.5 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">
                ¡Hola, {{ $adminFirstName }}!
                <span class="font-serif-display font-normal italic text-amber-300">resumen en vivo</span>
            </h1>
        </div>
        <div class="flex flex-wrap gap-2.5">
            <a href="{{ route('admin.sales.index') }}" class="btn-primary text-xs">
                <x-icon name="plus" class="size-4" />
                <span>Registrar venta</span>
            </a>
            <a href="{{ route('admin.site.edit') }}" class="btn-subtle text-xs">
                <x-icon name="globe" class="size-4 text-zinc-400" />
                <span>Editar página web</span>
            </a>
        </div>
    </div>

    {{-- KPI CARDS --}}
    <div class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($kpiCards as $card)
            <article data-cinema-enter data-enter-delay="{{ $loop->index + 1 }}"
                     class="panel spotlight-card rounded-3xl p-5 sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs font-bold text-zinc-400">{{ $card['label'] }}</p>
                    <span class="grid size-10 place-items-center rounded-2xl border {{ $card['badgeTone'] }}">
                        <x-icon :name="$card['icon']" class="size-4" />
                    </span>
                </div>
                <p class="font-mono-num mt-5 text-2xl font-extrabold tracking-tight text-white sm:text-3xl"
                   data-countup="{{ $card['raw'] }}" data-prefix="{{ $card['prefix'] }}" data-decimals="{{ $card['decimals'] }}">
                    {{ $card['prefix'] }}{{ number_format($card['raw'], $card['decimals']) }}
                </p>
                <p class="mt-2 text-[11px] font-medium text-zinc-500">{{ $card['hint'] }}</p>
            </article>
        @endforeach
    </div>

    {{-- REVENUE CHART & LOW STOCK --}}
    <div class="mt-5 grid gap-5 xl:grid-cols-[1.42fr_.78fr]">
        <section data-reveal class="panel spotlight-card rounded-3xl p-5 sm:p-7">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[.16em] text-fuchsia-300/90">Ritmo de ventas</p>
                    <h2 class="mt-1 text-lg font-extrabold tracking-tight text-white">Últimos seis meses</h2>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/3 px-3 py-1.5 text-[11px] font-bold text-zinc-300">
                    <x-icon name="bar-chart" class="size-3.5 text-fuchsia-300" />
                    PEN · ventas pagadas
                </span>
            </div>

            <div class="relative mt-8 h-52">
                {{-- Subtle chart gridlines --}}
                <div class="pointer-events-none absolute inset-x-0 bottom-7 top-2 flex flex-col justify-between">
                    @for($g = 0; $g < 4; $g++)
                        <div class="border-b border-dashed border-white/6"></div>
                    @endfor
                </div>

                <div class="relative z-10 flex h-full items-end gap-3 sm:gap-5">
                    @foreach($monthlySales as $month)
                        @php
                            $height = max(6, (float) $month['total'] / $maxSales * 100);
                        @endphp
                        <div class="group relative flex h-full flex-1 flex-col items-center justify-end gap-2.5">
                            {{-- Floating hover tooltip --}}
                            <div class="pointer-events-none absolute -top-2 z-20 hidden -translate-y-full whitespace-nowrap rounded-xl border border-white/15 bg-[#12121a]/95 px-3 py-1.5 text-center shadow-xl backdrop-blur-xl group-hover:block">
                                <p class="text-[10px] font-bold text-zinc-400">{{ $month['fullLabel'] ?? $month['label'] }}</p>
                                <p class="font-mono-num text-xs font-extrabold text-white">S/ {{ number_format((float) $month['total'], 2) }}</p>
                            </div>

                            <span class="font-mono-num text-[10px] font-semibold text-zinc-400 transition group-hover:text-fuchsia-200">
                                {{ number_format((float) $month['total'] / 1000, 1) }}k
                            </span>

                            <div class="flex h-36 w-full items-end justify-center">
                                <div class="chart-bar-fill relative w-full max-w-12 rounded-t-2xl bg-linear-to-t from-violet-600/85 via-fuchsia-500/90 to-fuchsia-300 shadow-lg shadow-fuchsia-500/15 transition duration-300 group-hover:brightness-125 group-hover:shadow-fuchsia-500/35"
                                     data-bar-height="{{ round($height, 2) }}"
                                     data-bar-index="{{ $loop->index }}">
                                    <span class="absolute inset-x-1.5 top-1 h-1 rounded-full bg-white/45"></span>
                                </div>
                            </div>

                            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 transition group-hover:text-white">{{ $month['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <p class="mt-5 border-t border-white/6 pt-4 text-[11px] text-zinc-500">
                La utilidad descuenta costos de compra y gastos directos por producto; no incluye gastos generales ni impuestos.
            </p>
        </section>

        <section data-reveal data-stagger="1" class="panel spotlight-card rounded-3xl p-5 sm:p-7">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[.16em] text-amber-300/90">Reposición</p>
                    <h2 class="mt-1 text-lg font-extrabold tracking-tight text-white">Stock por revisar</h2>
                </div>
                <a href="{{ route('admin.products.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-fuchsia-300 transition hover:text-fuchsia-200">
                    Inventario
                    <x-icon name="arrow-right" class="size-3.5" />
                </a>
            </div>

            <div class="mt-5 space-y-3">
                @forelse($lowStockProducts as $product)
                    <div class="flex items-center gap-3 rounded-2xl border border-amber-300/10 bg-white/2.5 p-3.5 transition hover:border-amber-300/25 hover:bg-white/4.5">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-amber-400/10 text-amber-300">
                            <x-icon name="alert-triangle" class="size-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-bold text-white">{{ $product->name }}</p>
                            <p class="mt-0.5 text-[10px] text-zinc-500">{{ $product->category }}</p>
                        </div>
                        <span class="font-mono-num shrink-0 rounded-full border border-amber-300/20 bg-amber-400/10 px-2.5 py-1 text-[11px] font-extrabold text-amber-200">
                            {{ number_format((float) $product->stock, 0) }} {{ $product->unit }}
                        </span>
                    </div>
                @empty
                    <div class="flex items-start gap-3 rounded-2xl border border-emerald-400/15 bg-emerald-400/5 p-4 text-xs leading-5 text-emerald-100">
                        <x-icon name="check-circle" class="mt-0.5 size-4 shrink-0 text-emerald-400" />
                        <span>Todo en orden. No hay productos por debajo del mínimo de reposición.</span>
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    {{-- RECENT SALES & LEADERS --}}
    <div class="mt-5 grid gap-5 xl:grid-cols-[1.42fr_.78fr]">
        <section data-reveal class="panel overflow-hidden rounded-3xl">
            <div class="flex items-center justify-between px-5 py-5 sm:px-7">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[.16em] text-zinc-400">Actividad reciente</p>
                    <h2 class="mt-1 text-lg font-extrabold tracking-tight text-white">Últimas ventas</h2>
                </div>
                <a href="{{ route('admin.sales.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-fuchsia-300 transition hover:text-fuchsia-200">
                    Ver todas
                    <x-icon name="arrow-right" class="size-3.5" />
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-135 text-left text-xs">
                    <thead class="bg-white/3 text-[10px] font-extrabold uppercase tracking-wider text-zinc-500">
                        <tr>
                            <th scope="col" class="px-6 py-3.5">Producto</th>
                            <th scope="col" class="px-4 py-3.5">Cliente</th>
                            <th scope="col" class="px-4 py-3.5">Fecha</th>
                            <th scope="col" class="px-6 py-3.5 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentSales as $sale)
                            <tr class="table-row">
                                <td class="px-6 py-4">
                                    <p class="font-bold text-white">{{ $sale->product?->name ?? 'Producto no disponible' }}</p>
                                    <p class="font-mono-num mt-0.5 text-[10px] text-zinc-500">{{ $sale->reference }}</p>
                                </td>
                                <td class="px-4 py-4 text-zinc-300">{{ $sale->client?->name ?? 'Venta al público' }}</td>
                                <td class="font-mono-num px-4 py-4 text-zinc-400">{{ $sale->sold_at?->format('d/m/Y') ?? '—' }}</td>
                                <td class="font-mono-num px-6 py-4 text-right font-extrabold text-white">S/ {{ number_format((float) $sale->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-zinc-500">
                                    Aún no tienes ventas. Registra la primera para activar tus métricas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section data-reveal data-stagger="1" class="panel spotlight-card rounded-3xl p-5 sm:p-7">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[.16em] text-zinc-400">Lo que más se mueve</p>
                    <h2 class="mt-1 text-lg font-extrabold tracking-tight text-white">Productos populares</h2>
                </div>
                <span class="grid size-9 place-items-center rounded-xl bg-amber-400/10 text-amber-300">
                    <x-icon name="sparkles" class="size-4" />
                </span>
            </div>

            <div class="mt-5 space-y-3.5">
                @php
                    $hasPopular = false;
                @endphp
                @foreach($topProducts as $product)
                    @if((float) ($product->units_sold ?? 0) > 0)
                        @php
                            $hasPopular = true;
                        @endphp
                        <div class="flex items-center gap-3">
                            <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-fuchsia-400/10 text-fuchsia-300">
                                <x-icon name="package" class="size-4" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-bold text-white">{{ $product->name }}</p>
                                <p class="text-[10px] text-zinc-500">{{ number_format((float) $product->units_sold, 0) }} unidades vendidas</p>
                            </div>
                            <span class="font-mono-num text-xs font-extrabold text-zinc-100">S/ {{ number_format((float) $product->revenue, 2) }}</span>
                        </div>
                    @endif
                @endforeach
                @if(!$hasPopular)
                    <p class="text-xs text-zinc-500">Tus productos más vendidos aparecerán aquí.</p>
                @endif
            </div>

            <div class="mt-6 border-t border-white/7 pt-4">
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-zinc-500">Clientes frecuentes</p>
                <div class="mt-3 space-y-2.5">
                    @forelse($topClients as $client)
                        <div class="flex items-center justify-between gap-3 text-xs">
                            <span class="truncate font-semibold text-zinc-200">{{ $client->name }}</span>
                            <span class="font-mono-num shrink-0 font-bold text-emerald-300">S/ {{ number_format((float) $client->revenue, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-[11px] text-zinc-500">Los clientes aparecerán cuando registres ventas asociadas.</p>
                    @endforelse
                </div>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-3">
                <div class="rounded-2xl border border-white/7 bg-white/3 p-3.5">
                    <p class="text-[10px] font-semibold text-zinc-500">Compras este mes</p>
                    <p class="font-mono-num mt-1 text-sm font-extrabold text-white">S/ {{ number_format((float) $monthPurchases, 2) }}</p>
                </div>
                <div class="rounded-2xl border border-white/7 bg-white/3 p-3.5">
                    <p class="text-[10px] font-semibold text-zinc-500">Consultas nuevas</p>
                    <p class="font-mono-num mt-1 text-sm font-extrabold text-white">{{ $newContactsTotal }}</p>
                </div>
            </div>
        </section>
    </div>
@endsection
