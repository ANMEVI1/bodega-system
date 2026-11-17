@extends('layouts.admin')
@section('title', 'Cliente · '.$client->name)
@section('heading', $client->name)
@section('content')
    @php
        $statusLabels = ['active' => 'Activo', 'follow-up' => 'Seguimiento', 'inactive' => 'Inactivo'];
        $clientTypeLabels = ['person' => 'Persona', 'business' => 'Empresa o negocio'];
        $documentTypeLabels = ['dni' => 'DNI', 'ruc' => 'RUC', 'ce' => 'Carné de extranjería', 'passport' => 'Pasaporte'];
        $contactLabels = ['whatsapp' => 'WhatsApp', 'phone' => 'Llamada telefónica', 'email' => 'Correo electrónico'];
        $sourceLabels = ['walk_in' => 'Visita a la tienda', 'referral' => 'Recomendación', 'social' => 'Redes sociales', 'whatsapp' => 'WhatsApp', 'other' => 'Otro'];
        $lastProduct = $lastPurchase?->product?->name;
        $messages = [
            'Seguimiento' => 'Hola '.$client->name.', te escribimos de Bodega Los Olivos. ¿En qué podemos ayudarte hoy?',
            'Reposición' => $lastProduct
                ? 'Hola '.$client->name.', ¿cómo te fue con '.$lastProduct.'? Si necesitas reponerlo, estamos para ayudarte.'
                : 'Hola '.$client->name.', ¿necesitas reponer algún producto? Estamos para ayudarte.',
            'Novedades' => 'Hola '.$client->name.', tenemos novedades en Bodega Los Olivos. Escríbenos y te contamos.',
        ];
    @endphp

    <div data-cinema-enter class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('admin.clients.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-zinc-400 transition hover:text-white">
                <x-icon name="arrow-left" class="size-4" />
                Volver a clientes
            </a>
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-extrabold tracking-tight text-white">{{ $client->name }}</h1>
                <span class="rounded-full bg-violet-400/10 px-2.5 py-1 text-[10px] font-bold text-violet-200 ring-1 ring-violet-400/20">
                    {{ $clientTypeLabels[$client->client_type] ?? $client->client_type }}
                </span>
                <span class="rounded-full px-2.5 py-1 text-[10px] font-bold {{ $client->status === 'active' ? 'bg-emerald-400/10 text-emerald-200 ring-1 ring-emerald-400/20' : ($client->status === 'follow-up' ? 'bg-amber-400/10 text-amber-200 ring-1 ring-amber-400/20' : 'bg-white/[.05] text-zinc-400') }}">
                    {{ $statusLabels[$client->status] ?? $client->status }}
                </span>
            </div>
            <p class="mt-1 text-xs text-zinc-400">{{ $client->email ?: 'Sin correo registrado' }} · +{{ $client->whatsappPhone() }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.sales.index', ['client_id' => $client->id]) }}" class="btn-primary text-xs">
                <x-icon name="plus" class="size-4" />
                Registrar venta
            </a>
            <a href="{{ route('admin.clients.edit', $client) }}" class="btn-subtle text-xs">
                <x-icon name="edit" class="size-4" />
                Editar cliente
            </a>
        </div>
    </div>

    <section data-cinema-enter style="--enter-delay: 1" class="panel mt-5 rounded-3xl p-5 sm:p-6">
        <div>
            <p class="text-[11px] font-extrabold uppercase tracking-[.18em] text-fuchsia-300">Información comercial</p>
            <h2 class="mt-1 text-lg font-extrabold text-white">Datos del cliente</h2>
        </div>
        <dl class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @if($client->document_type || $client->document_number)
                <div>
                    <dt class="text-[11px] font-semibold text-zinc-500">Documento</dt>
                    <dd class="mt-1 text-xs font-bold text-zinc-200">
                        {{ $documentTypeLabels[$client->document_type] ?? $client->document_type }} {{ $client->document_number }}
                    </dd>
                </div>
            @endif
            @if($client->contact_name)
                <div>
                    <dt class="text-[11px] font-semibold text-zinc-500">Persona de contacto</dt>
                    <dd class="mt-1 text-xs font-bold text-zinc-200">{{ $client->contact_name }}</dd>
                </div>
            @endif
            <div>
                <dt class="text-[11px] font-semibold text-zinc-500">Canal preferido</dt>
                <dd class="mt-1 text-xs font-bold text-zinc-200">{{ $contactLabels[$client->preferred_contact] ?? $client->preferred_contact }}</dd>
            </div>
            @if($client->address)
                <div>
                    <dt class="text-[11px] font-semibold text-zinc-500">Dirección</dt>
                    <dd class="mt-1 text-xs font-bold text-zinc-200">{{ $client->address }}</dd>
                </div>
            @endif
            @if($client->district || $client->city)
                <div>
                    <dt class="text-[11px] font-semibold text-zinc-500">Zona</dt>
                    <dd class="mt-1 text-xs font-bold text-zinc-200">{{ collect([$client->district, $client->city])->filter()->implode(', ') }}</dd>
                </div>
            @endif
            @if($client->source)
                <div>
                    <dt class="text-[11px] font-semibold text-zinc-500">Origen</dt>
                    <dd class="mt-1 text-xs font-bold text-zinc-200">{{ $sourceLabels[$client->source] ?? $client->source }}</dd>
                </div>
            @endif
        </dl>
    </section>

    <section data-cinema-enter style="--enter-delay: 2" class="panel mt-6 rounded-3xl p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-[11px] font-extrabold uppercase tracking-[.18em] text-emerald-300">Contacto rápido</p>
                <h2 class="mt-1 text-lg font-extrabold text-white">Mensajes predefinidos por WhatsApp</h2>
                <p class="mt-1 text-xs text-zinc-500">Se abrirá WhatsApp con el mensaje listo para revisar y enviar.</p>
            </div>
            <x-icon name="whatsapp" class="size-6 text-emerald-300" />
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            @foreach($messages as $label => $message)
                <a href="https://wa.me/{{ $client->whatsappPhone() }}?text={{ rawurlencode($message) }}"
                   target="_blank" rel="noopener"
                   class="btn-subtle text-xs">
                    <x-icon name="whatsapp" class="size-4 text-emerald-300" />
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </section>

    <div class="mt-5 grid gap-4 sm:grid-cols-3">
        <article data-cinema-enter style="--enter-delay: 3" class="panel rounded-2xl p-5">
            <p class="text-[11px] font-bold text-zinc-400">Compras pagadas</p>
            <p class="font-mono-num mt-2 text-2xl font-extrabold text-white">{{ $paidSalesCount }}</p>
        </article>
        <article data-cinema-enter style="--enter-delay: 4" class="panel rounded-2xl p-5">
            <p class="text-[11px] font-bold text-zinc-400">Total comprado</p>
            <p class="font-mono-num mt-2 text-2xl font-extrabold text-emerald-200">S/ {{ number_format((float) $totalSpent, 2) }}</p>
        </article>
        <article data-cinema-enter style="--enter-delay: 5" class="panel rounded-2xl p-5">
            <p class="text-[11px] font-bold text-zinc-400">Última compra pagada</p>
            <p class="mt-2 text-lg font-extrabold text-white">{{ $lastPurchase?->sold_at?->format('d/m/Y') ?? 'Sin compras' }}</p>
            <p class="mt-1 truncate text-[11px] text-zinc-500">{{ $lastProduct ?? 'Registra una venta para iniciar su historial.' }}</p>
        </article>
    </div>

    <section data-reveal class="panel mt-5 overflow-hidden rounded-3xl">
        <div class="px-6 py-5">
            <p class="text-[11px] font-bold uppercase tracking-[.16em] text-fuchsia-300">Preferencias de compra</p>
            <h2 class="mt-1 text-lg font-extrabold text-white">Productos que consume</h2>
            <p class="mt-1 text-xs text-zinc-500">Resumen calculado desde las ventas pagadas asociadas a este cliente.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] text-left text-xs">
                <thead class="bg-white/[.03] text-[10px] font-extrabold uppercase tracking-wider text-zinc-500">
                    <tr>
                        <th class="px-6 py-3.5">Producto</th>
                        <th class="px-4 py-3.5">Cantidad</th>
                        <th class="px-4 py-3.5">Total comprado</th>
                        <th class="px-6 py-3.5 text-right">Última compra</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr class="table-row">
                            <td class="px-6 py-4 font-bold text-white">{{ $product->name }}</td>
                            <td class="px-4 py-4 font-mono-num text-zinc-300">{{ number_format((float) $product->total_quantity, 2) }} {{ $product->unit }}</td>
                            <td class="px-4 py-4 font-mono-num font-bold text-emerald-200">S/ {{ number_format((float) $product->total_spent, 2) }}</td>
                            <td class="px-6 py-4 text-right text-zinc-400">{{ \Illuminate\Support\Carbon::parse($product->last_purchased_at)->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-zinc-500">
                                Aún no hay productos asociados. Registra una venta para este cliente y aparecerá aquí.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section data-reveal style="--stagger: 1" class="panel mt-5 overflow-hidden rounded-3xl">
        <div class="px-6 py-5">
            <p class="text-[11px] font-bold uppercase tracking-[.16em] text-zinc-400">Actividad del cliente</p>
            <h2 class="mt-1 text-lg font-extrabold text-white">Historial de ventas</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[600px] text-left text-xs">
                <thead class="bg-white/[.03] text-[10px] font-extrabold uppercase tracking-wider text-zinc-500">
                    <tr>
                        <th class="px-6 py-3.5">Referencia</th>
                        <th class="px-4 py-3.5">Producto</th>
                        <th class="px-4 py-3.5">Fecha</th>
                        <th class="px-4 py-3.5">Estado</th>
                        <th class="px-6 py-3.5 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                        <tr class="table-row">
                            <td class="px-6 py-4 font-mono-num font-bold text-fuchsia-200">{{ $sale->reference }}</td>
                            <td class="px-4 py-4">
                                <span class="font-bold text-white">{{ $sale->product->name }}</span>
                                <span class="ml-1 text-zinc-500">{{ number_format((float) $sale->quantity, 2) }} {{ $sale->product->unit }}</span>
                            </td>
                            <td class="px-4 py-4 text-zinc-400">{{ $sale->sold_at->format('d/m/Y') }}</td>
                            <td class="px-4 py-4">
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-bold {{ $sale->status === 'paid' ? 'bg-emerald-400/10 text-emerald-200' : 'bg-amber-400/10 text-amber-200' }}">
                                    {{ $sale->status === 'paid' ? 'Pagada' : 'Pendiente' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-mono-num font-extrabold text-white">S/ {{ number_format((float) $sale->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-zinc-500">Este cliente todavía no tiene ventas registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-white/[.06] px-6 py-4">{{ $sales->links() }}</div>
    </section>

    @if($client->notes)
        <section class="panel mt-5 rounded-2xl p-5">
            <h2 class="text-xs font-bold text-zinc-300">Notas internas</h2>
            <p class="mt-2 whitespace-pre-line text-xs leading-6 text-zinc-400">{{ $client->notes }}</p>
        </section>
    @endif

    <div class="mt-5 flex justify-end">
        <form method="POST" action="{{ route('admin.clients.destroy', $client) }}"
              data-confirm="¿Seguro que deseas eliminar a «{{ $client->name }}»? Las ventas se conservarán, pero quedarán sin cliente asociado.">
            @csrf @method('DELETE')
            <button class="btn-subtle text-xs text-rose-200 hover:text-rose-100">
                <x-icon name="trash" class="size-4" />
                Eliminar cliente
            </button>
        </form>
    </div>
@endsection
