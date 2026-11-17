@extends('layouts.admin')
@section('title', 'Ventas')
@section('heading', 'Ventas y finanzas')
@section('content')
    <div data-cinema-enter class="flex flex-wrap items-end justify-between gap-4 print:hidden">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.18em] text-fuchsia-300/90">Caja e historial</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-white">
                Ventas <span class="font-mono-num text-fuchsia-300">({{ $sales->total() }})</span>
            </h1>
        </div>
        <div class="flex gap-2.5">
            <a href="{{ route('admin.export', ['type' => 'sales']) }}" class="btn-subtle text-xs">
                <x-icon name="download" class="size-4 text-zinc-400" />
                <span>Excel (CSV)</span>
            </a>
            <button type="button" onclick="window.print()" class="btn-subtle text-xs">
                <x-icon name="printer" class="size-4 text-zinc-400" />
                <span>Imprimir / PDF</span>
            </button>
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4 print:hidden">
        @foreach([
            ['Ventas registradas', (float) $sales->total(), '', 0],
            ['Este mes', (float) $monthRevenue, 'S/ ', 2],
            ['Utilidad productos', (float) $monthProfit, 'S/ ', 2],
            ['Este trimestre', (float) $quarterRevenue, 'S/ ', 2],
        ] as [$label, $raw, $prefix, $decimals])
            <article data-cinema-enter style="--enter-delay: {{ $loop->index + 1 }}" class="panel spotlight-card rounded-2xl p-4 sm:p-5">
                <p class="text-[11px] font-bold text-zinc-400">{{ $label }}</p>
                <p class="font-mono-num mt-2 text-xl font-extrabold text-white"
                   data-countup="{{ $raw }}" data-prefix="{{ $prefix }}" data-decimals="{{ $decimals }}">
                    {{ $prefix }}{{ number_format($raw, $decimals) }}
                </p>
            </article>
        @endforeach
    </div>

    <div class="mt-5 grid gap-5 xl:grid-cols-[.82fr_1.18fr]">
        <form method="POST" data-sale-calc action="{{ route('admin.sales.store') }}" class="panel h-fit space-y-4 rounded-3xl p-6 print:hidden" data-reveal>
            @csrf
            <div>
                <p class="text-[11px] font-extrabold uppercase tracking-[.18em] text-fuchsia-300">Nueva operación</p>
                <h2 class="mt-1 text-lg font-extrabold text-white">Registrar una venta</h2>
            </div>

            @if($products->isEmpty())
                <p class="rounded-2xl border border-amber-400/20 bg-amber-400/10 p-3.5 text-xs text-amber-100">
                    Primero agrega productos activos al inventario para poder registrar ventas.
                </p>
            @endif

            <label class="block text-xs font-bold text-zinc-300">
                Producto
                <select class="field mt-2" name="product_id" required>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}"
                                data-price="{{ $product->selling_price }}"
                                data-stock="{{ $product->stock }}"
                                data-unit="{{ $product->unit }}"
                                @selected((string) old('product_id') === (string) $product->id)>
                            {{ $product->name }} · stock {{ number_format((float) $product->stock, 0) }} · S/ {{ number_format((float) $product->selling_price, 2) }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="block text-xs font-bold text-zinc-300">
                Cliente (opcional)
                <select class="field mt-2" name="client_id">
                    <option value="">Venta al público</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" @selected((string) old('client_id') === (string) $client->id)>{{ $client->name }}</option>
                    @endforeach
                </select>
            </label>

            <div class="grid grid-cols-2 gap-3">
                <label class="text-xs font-bold text-zinc-300">
                    Cantidad
                    <input class="field font-mono-num mt-2" type="number" step="0.01" min="0.01" name="quantity" value="{{ old('quantity', 1) }}" required>
                </label>
                <label class="text-xs font-bold text-zinc-300">
                    Precio unitario
                    <input class="field font-mono-num mt-2" type="number" step="0.01" min="0" name="unit_price" value="{{ old('unit_price') }}" placeholder="Precio base">
                </label>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <label class="text-xs font-bold text-zinc-300">
                    Fecha
                    <input class="field mt-2" type="date" name="sold_at" value="{{ old('sold_at', now()->toDateString()) }}" required>
                </label>
                <label class="text-xs font-bold text-zinc-300">
                    Estado
                    <select class="field mt-2" name="status">
                        <option value="paid" @selected(old('status', 'paid') === 'paid')>Pagada</option>
                        <option value="pending" @selected(old('status') === 'pending')>Pendiente</option>
                    </select>
                </label>
            </div>

            {{-- Live Sale Summary Preview --}}
            @if($products->isNotEmpty())
                <div class="rounded-2xl border border-white/[.08] bg-white/[.03] p-4">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-zinc-400">Stock disponible</span>
                        <span id="sale-preview-stock" class="font-mono-num font-bold text-zinc-200">—</span>
                    </div>
                    <div class="mt-2 flex items-center justify-between border-t border-white/[.06] pt-2">
                        <span class="text-xs font-bold text-zinc-300">Total estimado</span>
                        <span id="sale-preview-total" class="font-mono-num text-lg font-extrabold text-emerald-300">S/ 0.00</span>
                    </div>
                    <p id="sale-stock-warning" class="mt-2 hidden rounded-xl border border-rose-400/25 bg-rose-500/10 px-3 py-2 text-[11px] font-semibold text-rose-200">
                        La cantidad supera el stock disponible.
                    </p>
                </div>
            @endif

            <p class="text-[11px] leading-5 text-zinc-500">
                La utilidad descuenta el costo y gasto unitario guardados al momento de registrar la venta.
            </p>

            <button class="btn-primary w-full" @disabled($products->isEmpty())>
                <x-icon name="check-circle" class="size-4" />
                <span>Guardar venta</span>
            </button>
        </form>

        <section data-reveal style="--stagger: 1" class="panel overflow-hidden rounded-3xl">
            <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-5">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[.16em] text-zinc-400">Movimientos</p>
                    <h2 class="mt-1 text-lg font-extrabold text-white">Historial de ventas</h2>
                </div>
                <form method="GET" class="print:hidden">
                    <select class="field py-2 text-xs" name="period" onchange="this.form.submit()">
                        <option value="">Todo el historial</option>
                        <option value="month" @selected(request('period') === 'month')>Último mes</option>
                        <option value="quarter" @selected(request('period') === 'quarter')>Este trimestre</option>
                    </select>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[690px] text-left text-xs">
                    <thead class="bg-white/[.03] text-[10px] font-extrabold uppercase tracking-wider text-zinc-500">
                        <tr>
                            <th class="px-6 py-3.5">Venta</th>
                            <th class="px-4 py-3.5">Producto / cliente</th>
                            <th class="px-4 py-3.5">Fecha</th>
                            <th class="px-4 py-3.5">Estado</th>
                            <th class="px-6 py-3.5 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $sale)
                            <tr class="table-row">
                                <td class="font-mono-num px-6 py-4 font-bold text-fuchsia-200">{{ $sale->reference }}</td>
                                <td class="px-4 py-4">
                                    <p class="font-bold text-white">{{ $sale->product->name }}</p>
                                    <p class="mt-1 text-[11px] text-zinc-500">{{ $sale->client?->name ?? 'Venta al público' }} · {{ number_format((float) $sale->quantity, 2) }} u</p>
                                </td>
                                <td class="font-mono-num px-4 py-4 text-zinc-400">{{ $sale->sold_at->format('d/m/Y') }}</td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold {{ $sale->status === 'paid' ? 'bg-emerald-400/10 text-emerald-200 ring-1 ring-emerald-400/20' : 'bg-amber-400/10 text-amber-200 ring-1 ring-amber-400/20' }}">
                                        <span class="size-1.5 rounded-full {{ $sale->status === 'paid' ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
                                        {{ $sale->status === 'paid' ? 'Pagada' : 'Pendiente' }}
                                    </span>
                                </td>
                                <td class="font-mono-num px-6 py-4 text-right font-extrabold text-white">S/ {{ number_format((float) $sale->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-14 text-center text-zinc-500">
                                    Aún no hay ventas en este período.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-white/[.06] px-6 py-4 print:hidden">{{ $sales->links() }}</div>
        </section>
    </div>
@endsection
