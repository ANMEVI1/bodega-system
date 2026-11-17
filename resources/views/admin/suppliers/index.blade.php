@extends('layouts.admin')
@section('title', 'Proveedores')
@section('heading', 'Cartera de proveedores')
@section('content')
    {{-- Cabecera del módulo --}}
    <div data-cinema-enter class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.18em] text-cyan-300/90">Red de abastecimiento</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-white">
                Cartera de Proveedores <span class="font-mono-num text-fuchsia-300">({{ $suppliers->total() }})</span>
            </h1>
            <p class="mt-1 text-xs text-zinc-400">Administra contactos, filtra por rubro o condición comercial, escribe directo por WhatsApp y revisa compras acumuladas por producto.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <div class="inline-flex items-center gap-2 rounded-2xl border border-emerald-400/20 bg-emerald-400/[.06] px-3.5 py-2 text-[11px] font-semibold text-emerald-100">
                <x-icon name="whatsapp" class="size-3.5 text-emerald-300" />
                <span class="text-zinc-400">Predeterminada:</span>
                <select id="global-wa-default" aria-label="Plantilla de WhatsApp predeterminada"
                        class="bg-transparent font-bold text-emerald-200 outline-none">
                    @foreach(\App\Models\Supplier::WHATSAPP_TEMPLATES as $key => $tpl)
                        <option class="bg-[#14141a] text-white" value="{{ $key }}">{{ $tpl['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <a href="{{ route('admin.suppliers.create') }}" class="btn-primary text-xs">
                <x-icon name="plus" class="size-4" />
                <span>Agregar proveedor</span>
            </a>
        </div>
    </div>

    {{-- KPIs del módulo Proveedores --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['Proveedores en cartera', (float) $portfolioStats['total_suppliers'], '', 0, $portfolioStats['active_suppliers'].' activos actualmente', 'truck', 'text-cyan-300 bg-cyan-400/10 border-cyan-400/20'],
            ['Rubros cubiertos', (float) $portfolioStats['specialties_count'], '', 0, 'Especialidades registradas', 'sparkles', 'text-fuchsia-300 bg-fuchsia-400/10 border-fuchsia-400/20'],
            ['Inversión acumulada', (float) $portfolioStats['total_invested'], 'S/ ', 2, $portfolioStats['total_orders'].' órdenes registradas', 'wallet', 'text-emerald-300 bg-emerald-400/10 border-emerald-400/20'],
            ['Productos con historial', (float) $portfolioStats['tracked_products'], '', 0, 'Estadísticas listas por producto', 'bar-chart', 'text-amber-300 bg-amber-400/10 border-amber-400/20'],
        ] as [$label, $raw, $prefix, $decimals, $hint, $icon, $badgeTone])
            <article data-cinema-enter style="--enter-delay: {{ $loop->index + 1 }}" class="panel spotlight-card rounded-3xl p-5">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs font-bold text-zinc-400">{{ $label }}</p>
                    <span class="grid size-9 place-items-center rounded-xl border {{ $badgeTone }}">
                        <x-icon :name="$icon" class="size-4" />
                    </span>
                </div>
                <p class="font-mono-num mt-4 text-2xl font-extrabold text-white"
                   data-countup="{{ $raw }}" data-prefix="{{ $prefix }}" data-decimals="{{ $decimals }}">
                    {{ $prefix }}{{ number_format($raw, $decimals) }}
                </p>
                <p class="mt-1.5 text-[11px] text-zinc-500">{{ $hint }}</p>
            </article>
        @endforeach
    </div>

    {{-- Barra de búsqueda y filtros avanzados --}}
    <form data-cinema-enter style="--enter-delay: 2" method="GET" class="panel mt-5 rounded-3xl p-4 sm:p-5">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[1.5fr_1fr_.85fr_.95fr_.95fr_auto]">
            <div class="relative">
                <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-zinc-500" />
                <input class="field pl-10 text-xs" name="q" value="{{ $filters['q'] }}" placeholder="Buscar por nombre, RUC, contacto, rubro o teléfono…">
            </div>

            <select class="field text-xs" name="specialty" aria-label="Filtrar por rubro">
                <option value="">Todos los rubros</option>
                @foreach($specialties as $specialty)
                    <option value="{{ $specialty }}" @selected($filters['specialty'] === $specialty)>{{ $specialty }}</option>
                @endforeach
            </select>

            <select class="field text-xs" name="status" aria-label="Filtrar por estado">
                <option value="">Todos los estados</option>
                @foreach(\App\Models\Supplier::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select class="field text-xs" name="payment_terms" aria-label="Filtrar por condición de pago">
                <option value="">Cualquier pago</option>
                @foreach(\App\Models\Supplier::PAYMENT_TERMS as $value => $label)
                    <option value="{{ $value }}" @selected($filters['payment_terms'] === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select class="field text-xs" name="sort" aria-label="Ordenar proveedores">
                <option value="name_asc" @selected($filters['sort'] === 'name_asc')>Orden: A – Z</option>
                <option value="spend_desc" @selected($filters['sort'] === 'spend_desc')>Mayor inversión</option>
                <option value="orders_desc" @selected($filters['sort'] === 'orders_desc')>Más órdenes</option>
                <option value="recent" @selected($filters['sort'] === 'recent')>Más recientes</option>
            </select>

            <div class="flex gap-2">
                <button class="btn-primary flex-1 text-xs xl:flex-initial">Filtrar</button>
                @if($filters['q'] !== '' || $filters['specialty'] !== '' || $filters['status'] !== '' || $filters['payment_terms'] !== '' || $filters['sort'] !== 'name_asc')
                    <a href="{{ route('admin.suppliers.index') }}" class="btn-subtle px-3 text-xs" title="Limpiar filtros">Limpiar</a>
                @endif
            </div>
        </div>
    </form>

    {{-- Tarjetas de Proveedores --}}
    <div class="mt-5 grid gap-5 xl:grid-cols-2">
        @forelse($suppliers as $supplier)
            @php
                $isActive = ($supplier->status ?: 'active') === 'active';
                $productStats = $supplier->getAttribute('product_stats') ?? collect();
                $totalSpent = (float) ($supplier->purchases_sum_total ?? 0);
                $totalUnits = (float) ($supplier->purchases_sum_quantity ?? 0);
                $ordersCount = (int) ($supplier->purchases_count ?? 0);
            @endphp

            <article data-reveal style="--stagger: {{ $loop->index % 2 }}"
                     data-supplier-card
                     data-supplier-name="{{ $supplier->name }}"
                     data-supplier-phone="{{ $supplier->whatsappDigits() }}"
                     class="panel spotlight-card flex flex-col rounded-3xl p-6">
                {{-- 1. Cabecera del proveedor --}}
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex items-start gap-3.5">
                        <span class="grid size-12 shrink-0 place-items-center rounded-2xl border border-cyan-400/25 bg-gradient-to-br from-cyan-500/20 to-violet-600/20 text-sm font-black text-cyan-200">
                            {{ $supplier->initials() }}
                        </span>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-base font-extrabold text-white">{{ $supplier->name }}</h2>
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $isActive ? 'bg-emerald-400/10 text-emerald-200 ring-1 ring-emerald-400/20' : 'bg-zinc-500/15 text-zinc-400 ring-1 ring-white/10' }}">
                                    <span class="size-1.5 rounded-full {{ $isActive ? 'bg-emerald-400' : 'bg-zinc-500' }}"></span>
                                    {{ $supplier->statusLabel() }}
                                </span>
                            </div>

                            <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] text-zinc-400">
                                <span>{{ $supplier->specialty ?: 'Proveedor general' }}</span>
                                @if($supplier->contact_name)
                                    <span class="text-zinc-600">·</span>
                                    <span>Contacto: <strong class="font-semibold text-zinc-200">{{ $supplier->contact_name }}</strong></span>
                                @endif
                                @if($supplier->tax_id)
                                    <span class="text-zinc-600">·</span>
                                    <span class="font-mono-num text-zinc-300">RUC {{ $supplier->tax_id }}</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button"
                                data-wa-open
                                data-supplier-name="{{ $supplier->name }}"
                                data-supplier-contact="{{ $supplier->contact_name ?: '' }}"
                                data-supplier-phone="{{ $supplier->whatsappDigits() }}"
                                data-supplier-phone-raw="{{ $supplier->phone }}"
                                data-supplier-default-tpl="{{ $supplier->whatsapp_template ?: '' }}"
                                data-wa-templates='@json($supplier->resolvedWhatsappTemplates())'
                                class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-400/30 bg-emerald-400/15 px-3.5 py-2 text-[11px] font-extrabold text-emerald-100 shadow-lg shadow-emerald-500/10 transition hover:bg-emerald-400/25">
                            <x-icon name="whatsapp" class="size-3.5 text-emerald-300" />
                            <span>WhatsApp</span>
                        </button>
                        <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="btn-subtle px-3 py-2 text-[11px]">
                            <x-icon name="edit" class="size-3.5" />
                            Editar
                        </a>
                    </div>
                </div>

                {{-- 2. Información de contacto --}}
                <div class="mt-4 space-y-2.5 border-y border-white/[.06] py-3.5 text-xs">
                    <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
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
                        @if($supplier->paymentTermsLabel())
                            <span class="rounded-full border border-white/10 bg-white/[.04] px-2.5 py-0.5 text-[10px] font-bold text-zinc-300">
                                {{ $supplier->paymentTermsLabel() }}
                            </span>
                        @endif
                        @if($supplier->whatsapp_template && isset(\App\Models\Supplier::WHATSAPP_TEMPLATES[$supplier->whatsapp_template]))
                            <span class="inline-flex items-center gap-1 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-2.5 py-0.5 text-[10px] font-bold text-emerald-200">
                                <x-icon name="whatsapp" class="size-3" />
                                {{ \App\Models\Supplier::WHATSAPP_TEMPLATES[$supplier->whatsapp_template]['label'] }}
                            </span>
                        @endif
                    </div>

                    @if($supplier->address || $supplier->notes)
                        <div class="space-y-1 text-[11px] text-zinc-400">
                            @if($supplier->address)
                                <p><span class="font-semibold text-zinc-300">Dirección:</span> {{ $supplier->address }}</p>
                            @endif
                            @if($supplier->notes)
                                <p class="line-clamp-2 text-zinc-400"><span class="font-semibold text-zinc-300">Nota:</span> {{ $supplier->notes }}</p>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- 3. Estadísticas de compras acumuladas por cada producto --}}
                <div class="mt-4 flex-1">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-[10px] font-extrabold uppercase tracking-[.16em] text-zinc-400">
                            Estadísticas de compras acumuladas por producto
                        </p>
                        <div class="flex items-center gap-3 text-[11px]">
                            <span class="font-mono-num text-zinc-400">{{ $ordersCount }} {{ $ordersCount === 1 ? 'orden' : 'órdenes' }}</span>
                            <span class="font-mono-num font-extrabold text-emerald-300">S/ {{ number_format($totalSpent, 2) }}</span>
                        </div>
                    </div>

                    @if($productStats->isNotEmpty())
                        <div class="mt-3 space-y-2.5">
                            @foreach($productStats as $stat)
                                <div class="rounded-2xl border border-white/[.06] bg-white/[.025] p-3">
                                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono-num rounded-lg bg-fuchsia-500/15 px-2 py-0.5 text-[10px] font-bold text-fuchsia-200 ring-1 ring-fuchsia-400/20">
                                                {{ $stat['product_code'] }}
                                            </span>
                                            <span class="font-bold text-zinc-100">Producto vinculado #{{ $stat['product_id'] }}</span>
                                        </div>
                                        <span class="font-mono-num font-extrabold text-white">S/ {{ number_format($stat['total_spent'], 2) }}</span>
                                    </div>

                                    <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-[11px] text-zinc-400">
                                        <span class="font-mono-num">
                                            {{ number_format($stat['total_quantity'], 0) }} u acumuladas · Costo prom. S/ {{ number_format($stat['avg_unit_cost'], 2) }}
                                        </span>
                                        <span class="font-mono-num text-zinc-500">
                                            {{ $stat['orders_count'] }}x · Última {{ \Illuminate\Support\Carbon::parse($stat['last_purchased_at'])->format('d/m/Y') }}
                                        </span>
                                    </div>

                                    <div class="mt-2 flex items-center gap-2">
                                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-white/[.06]">
                                            <div class="h-full rounded-full bg-gradient-to-r from-cyan-400 to-fuchsia-400" style="width: {{ max(4, $stat['share_percent']) }}%"></div>
                                        </div>
                                        <span class="font-mono-num w-11 text-right text-[10px] font-bold text-zinc-400">{{ number_format($stat['share_percent'], 1) }}%</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-2 text-[10px] text-zinc-500">
                            Total acumulado en {{ number_format($totalUnits, 0) }} unidades. Los nombres comerciales se mostrarán automáticamente al conectar el módulo Productos.
                        </p>
                    @else
                        <div class="mt-3 rounded-2xl border border-dashed border-white/10 bg-white/[.015] p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold text-zinc-300">Sin compras acumuladas por producto todavía</p>
                                    <p class="mt-1 text-[11px] leading-5 text-zinc-500">
                                        Cuando el módulo <strong class="text-zinc-300">Productos</strong> registre abastecimientos para este proveedor, verás aquí el desglose por ítem, unidades, costo promedio e inversión acumulada.
                                    </p>
                                </div>
                                <span class="shrink-0 rounded-full border border-white/10 bg-white/[.04] px-2.5 py-1 text-[10px] font-bold text-zinc-400">En espera</span>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- 4. Diseño de interfaz preparado para futura conexión con Módulo Productos (sin tocar Producto aún) --}}
                <details class="group mt-4 rounded-2xl border border-fuchsia-400/20 bg-fuchsia-500/[.03]">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-4 py-3 text-xs font-bold text-fuchsia-200 transition hover:text-fuchsia-100">
                        <span class="inline-flex items-center gap-2">
                            <x-icon name="package" class="size-4 text-fuchsia-300" />
                            <span>Conexión con Productos · Diseño de enlace</span>
                        </span>
                        <span class="inline-flex items-center gap-2">
                            <span class="rounded-full border border-fuchsia-400/25 bg-fuchsia-500/10 px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-wider text-fuchsia-200">Pendiente</span>
                            <x-icon name="chevron-down" class="size-4 text-zinc-400 transition group-open:rotate-180" />
                        </span>
                    </summary>

                    <div class="space-y-3 border-t border-fuchsia-400/15 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-zinc-400">
                            <span>Interfaz preparada para registrar abastecimientos cuando el módulo <strong class="text-zinc-200">Productos</strong> esté listo.</span>
                            <code class="font-mono-num rounded-md bg-black/30 px-2 py-0.5 text-[10px] text-fuchsia-300">supplier_id: {{ $supplier->id }}</code>
                        </div>

                        <div class="grid gap-3 opacity-75 sm:grid-cols-2" aria-disabled="true">
                            <label class="text-[11px] font-bold text-zinc-400 sm:col-span-2">
                                Producto del catálogo (Módulo Productos)
                                <select class="field mt-1.5 cursor-not-allowed text-xs" disabled>
                                    <option>Se habilitará al conectar el módulo Productos…</option>
                                </select>
                            </label>
                            <label class="text-[11px] font-bold text-zinc-400">
                                Cantidad abastecida
                                <input class="field font-mono-num mt-1.5 cursor-not-allowed text-xs" type="text" value="0.00" disabled>
                            </label>
                            <label class="text-[11px] font-bold text-zinc-400">
                                Costo unitario (S/)
                                <input class="field font-mono-num mt-1.5 cursor-not-allowed text-xs" type="text" value="0.00" disabled>
                            </label>
                            <label class="text-[11px] font-bold text-zinc-400">
                                Fecha de compra
                                <input class="field mt-1.5 cursor-not-allowed text-xs" type="date" value="{{ now()->toDateString() }}" disabled>
                            </label>
                            <label class="text-[11px] font-bold text-zinc-400">
                                Comprobante / Referencia
                                <input class="field font-mono-num mt-1.5 cursor-not-allowed text-xs" type="text" placeholder="FAC-001" disabled>
                            </label>
                        </div>

                        <button type="button" disabled
                            class="flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-2xl border border-fuchsia-400/20 bg-fuchsia-500/10 px-4 py-2.5 text-xs font-bold text-fuchsia-200/70">
                            <x-icon name="package" class="size-3.5" />
                            <span>Registrar compra (habilitado al integrar Productos)</span>
                        </button>
                    </div>
                </details>

                {{-- Pie de tarjeta: eliminar proveedor --}}
                <div class="mt-4 flex items-center justify-between border-t border-white/[.05] pt-3">
                    <span class="text-[10px] text-zinc-500">Registrado el {{ $supplier->created_at?->format('d/m/Y') ?? '—' }}</span>
                    <form method="POST" action="{{ route('admin.suppliers.destroy', $supplier) }}"
                          data-confirm="¿Seguro que deseas eliminar a «{{ $supplier->name }}» de tu cartera?">
                        @csrf @method('DELETE')
                        <button class="inline-flex items-center gap-1 rounded-xl px-2.5 py-1.5 text-[11px] font-bold text-rose-300/80 transition hover:bg-rose-500/10 hover:text-rose-200">
                            <x-icon name="trash" class="size-3.5" />
                            Eliminar proveedor
                        </button>
                    </form>
                </div>
            </article>
        @empty
            <div class="panel rounded-3xl p-12 text-center xl:col-span-2">
                <span class="mx-auto grid size-12 place-items-center rounded-2xl border border-cyan-400/20 bg-cyan-400/10 text-cyan-300">
                    <x-icon name="truck" class="size-5" />
                </span>
                <h2 class="mt-4 text-base font-extrabold text-white">No hay proveedores que coincidan</h2>
                <p class="mt-1.5 text-xs text-zinc-400">Prueba ajustando los filtros de búsqueda o registra el primer proveedor de tu cartera.</p>
                <div class="mt-5 flex justify-center gap-2.5">
                    @if($filters['q'] !== '' || $filters['specialty'] !== '' || $filters['status'] !== '' || $filters['payment_terms'] !== '')
                        <a href="{{ route('admin.suppliers.index') }}" class="btn-subtle text-xs">Limpiar filtros</a>
                    @endif
                    <a href="{{ route('admin.suppliers.create') }}" class="btn-primary text-xs">
                        <x-icon name="plus" class="size-4" />
                        <span>Agregar proveedor</span>
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $suppliers->links() }}</div>

    {{-- Modal selector de plantillas de WhatsApp (previo al envío directo) --}}
    <div id="supplier-wa-modal"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black/75 p-4 backdrop-blur-md"
         role="dialog" aria-modal="true" aria-labelledby="wa-modal-title">
        <div class="panel w-full max-w-xl rounded-3xl p-6 shadow-2xl sm:p-7" data-cinema-enter>
            <div class="flex items-start justify-between gap-4 border-b border-white/[.08] pb-4">
                <div class="flex items-center gap-3">
                    <span class="grid size-11 shrink-0 place-items-center rounded-2xl border border-emerald-400/30 bg-emerald-400/15 text-emerald-300">
                        <x-icon name="whatsapp" class="size-5" />
                    </span>
                    <div>
                        <p class="text-[10px] font-extrabold uppercase tracking-[.18em] text-emerald-300">Contactar proveedor</p>
                        <h2 id="wa-modal-title" data-wa-modal-supplier class="text-base font-extrabold text-white">Proveedor</h2>
                        <p data-wa-modal-meta class="font-mono-num mt-0.5 text-[11px] text-zinc-400">+51...</p>
                    </div>
                </div>
                <button type="button" data-wa-modal-close class="grid size-8 place-items-center rounded-full border border-white/10 bg-white/[.05] text-zinc-400 transition hover:text-white" aria-label="Cerrar">
                    <x-icon name="x" class="size-4" />
                </button>
            </div>

            <div class="mt-5">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-[11px] font-extrabold uppercase tracking-wider text-zinc-400">Elige una plantilla de mensaje</p>
                    <span data-wa-default-badge class="rounded-full border border-emerald-400/25 bg-emerald-400/10 px-2.5 py-0.5 text-[10px] font-bold text-emerald-200">
                        Predeterminada aplicada
                    </span>
                </div>

                <div data-wa-template-list class="mt-3 grid gap-2 sm:grid-cols-2"></div>
            </div>

            <div class="mt-4">
                <label class="block text-xs font-bold text-zinc-300">
                    Vista previa del mensaje a enviar
                    <textarea data-wa-message-box class="field mt-2 min-h-24 text-xs leading-6" maxlength="1000" placeholder="Escribe o ajusta el mensaje antes de abrir WhatsApp…"></textarea>
                </label>

                <label class="mt-3 inline-flex cursor-pointer items-center gap-2 text-[11px] font-semibold text-zinc-400 hover:text-zinc-200">
                    <input type="checkbox" data-wa-save-default class="size-4 rounded accent-emerald-400">
                    <span>Guardar esta opción como plantilla predeterminada de la cartera</span>
                </label>
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-end gap-2.5 border-t border-white/[.08] pt-4">
                <button type="button" data-wa-modal-close class="btn-subtle text-xs">Cancelar</button>
                <a href="#" target="_blank" rel="noopener" data-wa-send-link
                   class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-400 to-teal-400 px-5 py-2.5 text-xs font-extrabold text-emerald-950 shadow-lg shadow-emerald-500/20 transition hover:brightness-110">
                    <x-icon name="whatsapp" class="size-4" />
                    <span>Enviar por WhatsApp</span>
                    <x-icon name="arrow-up-right" class="size-3.5" />
                </a>
            </div>
        </div>
    </div>

    {{-- Lógica interactiva local del módulo Proveedores (Selector de plantillas WhatsApp) --}}
    <script>
        (() => {
            const DEFAULT_STORAGE_KEY = 'nexo.supplier_wa_default_template';
            const globalSelect = document.getElementById('global-wa-default');
            const modal = document.getElementById('supplier-wa-modal');
            if (!modal) return;

            const supplierTitle = modal.querySelector('[data-wa-modal-supplier]');
            const supplierMeta = modal.querySelector('[data-wa-modal-meta]');
            const templateList = modal.querySelector('[data-wa-template-list]');
            const messageBox = modal.querySelector('[data-wa-message-box]');
            const saveDefaultCheckbox = modal.querySelector('[data-wa-save-default]');
            const defaultBadge = modal.querySelector('[data-wa-default-badge]');
            const sendLink = modal.querySelector('[data-wa-send-link]');

            let activePhone = '';
            let activeKey = 'disponibilidad';
            let activeTemplates = {};

            const getGlobalDefault = () => {
                const stored = localStorage.getItem(DEFAULT_STORAGE_KEY);
                return stored && globalSelect?.querySelector(`option[value="${stored}"]`) ? stored : 'disponibilidad';
            };

            const setGlobalDefault = (key) => {
                localStorage.setItem(DEFAULT_STORAGE_KEY, key);
                if (globalSelect) globalSelect.value = key;
            };

            if (globalSelect) {
                globalSelect.value = getGlobalDefault();
                globalSelect.addEventListener('change', () => setGlobalDefault(globalSelect.value));
            }

            const syncSendHref = () => {
                const text = encodeURIComponent((messageBox?.value || '').trim());
                if (sendLink) {
                    sendLink.href = `https://wa.me/${activePhone}?text=${text}`;
                }
            };

            const selectTemplate = (key) => {
                activeKey = activeTemplates[key] ? key : Object.keys(activeTemplates)[0];
                const tpl = activeTemplates[activeKey];
                if (!tpl) return;

                if (messageBox) {
                    messageBox.value = tpl.message;
                    syncSendHref();
                }

                templateList?.querySelectorAll('[data-tpl-key]').forEach((btn) => {
                    const selected = btn.dataset.tplKey === activeKey;
                    btn.classList.toggle('border-emerald-400/50', selected);
                    btn.classList.toggle('bg-emerald-400/15', selected);
                    btn.classList.toggle('border-white/10', !selected);
                    btn.classList.toggle('bg-white/[.03]', !selected);
                    btn.setAttribute('aria-pressed', selected ? 'true' : 'false');
                });
            };

            const openModal = (trigger) => {
                activePhone = trigger.dataset.supplierPhone || '';
                const name = trigger.dataset.supplierName || 'Proveedor';
                const contact = trigger.dataset.supplierContact || '';
                const phoneRaw = trigger.dataset.supplierPhoneRaw || activePhone;
                const supplierDefault = trigger.dataset.supplierDefaultTpl || '';
                activeTemplates = JSON.parse(trigger.dataset.waTemplates || '{}');

                const initialKey = supplierDefault && activeTemplates[supplierDefault]
                    ? supplierDefault
                    : getGlobalDefault();

                if (supplierTitle) supplierTitle.textContent = name;
                if (supplierMeta) supplierMeta.textContent = contact ? `${contact} · ${phoneRaw}` : phoneRaw;
                if (defaultBadge) {
                    defaultBadge.textContent = supplierDefault
                        ? 'Predeterminada del proveedor'
                        : 'Predeterminada de la cartera';
                }
                if (saveDefaultCheckbox) saveDefaultCheckbox.checked = false;

                if (templateList) {
                    templateList.innerHTML = '';
                    Object.values(activeTemplates).forEach((tpl) => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.dataset.tplKey = tpl.key;
                        btn.className = 'rounded-2xl border border-white/10 bg-white/[.03] p-3 text-left transition hover:border-emerald-400/35 hover:bg-emerald-400/[.08]';

                        const titleEl = document.createElement('p');
                        titleEl.className = 'text-xs font-extrabold text-white';
                        titleEl.textContent = tpl.label;

                        const descEl = document.createElement('p');
                        descEl.className = 'mt-0.5 text-[10px] leading-4 text-zinc-400';
                        descEl.textContent = tpl.description;

                        btn.append(titleEl, descEl);
                        btn.addEventListener('click', () => selectTemplate(tpl.key));
                        templateList.appendChild(btn);
                    });
                }

                selectTemplate(initialKey);
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                messageBox?.focus();
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            document.querySelectorAll('[data-wa-open]').forEach((btn) => {
                btn.addEventListener('click', () => openModal(btn));
            });

            modal.querySelectorAll('[data-wa-modal-close]').forEach((btn) => {
                btn.addEventListener('click', closeModal);
            });

            modal.addEventListener('click', (event) => {
                if (event.target === modal) closeModal();
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
            });

            messageBox?.addEventListener('input', syncSendHref);

            sendLink?.addEventListener('click', () => {
                if (saveDefaultCheckbox?.checked && activeKey) {
                    setGlobalDefault(activeKey);
                }
                setTimeout(closeModal, 120);
            });
        })();
    </script>
@endsection
