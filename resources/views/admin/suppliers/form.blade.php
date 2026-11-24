@extends('layouts.admin')
@section('title', $supplier->exists ? 'Editar proveedor' : 'Agregar proveedor')
@section('heading', $supplier->exists ? 'Editar proveedor' : 'Nuevo proveedor')
@section('content')
    <div class="mx-auto max-w-3xl" data-cinema-enter>
        <a href="{{ route('admin.suppliers.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-zinc-400 transition hover:text-white">
            <x-icon name="arrow-left" class="size-4" />
            Volver a cartera de proveedores
        </a>

        <form method="POST"
              action="{{ $supplier->exists ? route('admin.suppliers.update', $supplier) : route('admin.suppliers.store') }}"
              class="panel mt-5 space-y-6 rounded-3xl p-6 sm:p-8">
            @csrf
            @if($supplier->exists)
                @method('PUT')
            @endif

            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-white/[.07] pb-5">
                <div>
                    <p class="text-[11px] font-extrabold uppercase tracking-[.18em] text-cyan-300">Cartera de proveedores</p>
                    <h1 class="mt-1 text-2xl font-extrabold text-white">
                        {{ $supplier->exists ? 'Actualiza la ficha del proveedor' : 'Registra un nuevo proveedor' }}
                    </h1>
                    <p class="mt-1 text-xs text-zinc-400">Centraliza sus datos comerciales, contacto directo por WhatsApp y condiciones de compra.</p>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1 text-[10px] font-bold text-cyan-200">
                    <x-icon name="truck" class="size-3.5" />
                    Módulo Proveedores
                </span>
            </div>

            {{-- Bloque 1: Identidad comercial --}}
            <div class="space-y-4">
                <p class="text-[10px] font-extrabold uppercase tracking-[.2em] text-zinc-500">1. Identidad comercial</p>
                <div class="grid gap-4 sm:grid-cols-3">
                    <label class="text-xs font-bold text-zinc-300 sm:col-span-2">
                        Nombre o razón social
                        <input class="field mt-2" name="name" value="{{ old('name', $supplier->name) }}" maxlength="160" placeholder="Ej. Distribuidora Santa Clara SAC" required>
                    </label>
                    <label class="text-xs font-bold text-zinc-300">
                        RUC / Documento (opcional)
                        <input class="field font-mono-num mt-2" name="tax_id" value="{{ old('tax_id', $supplier->tax_id) }}" maxlength="32" placeholder="20123456789">
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <label class="text-xs font-bold text-zinc-300">
                        Rubro / especialidad
                        <input class="field mt-2" name="specialty" value="{{ old('specialty', $supplier->specialty) }}" maxlength="160" placeholder="Ej. Abarrotes y aceites">
                    </label>
                    <label class="text-xs font-bold text-zinc-300">
                        Condición de pago
                        <select class="field mt-2" name="payment_terms">
                            <option value="">Sin definir</option>
                            @foreach(\App\Models\Supplier::PAYMENT_TERMS as $value => $label)
                                <option value="{{ $value }}" @selected(old('payment_terms', $supplier->payment_terms) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-xs font-bold text-zinc-300">
                        Estado en cartera
                        <select class="field mt-2" name="status">
                            @foreach(\App\Models\Supplier::STATUSES as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $supplier->status ?: 'active') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </div>

            {{-- Bloque 2: Contacto directo --}}
            <div class="space-y-4 border-t border-white/[.06] pt-5">
                <p class="text-[10px] font-extrabold uppercase tracking-[.2em] text-zinc-500">2. Contacto directo y WhatsApp</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="text-xs font-bold text-zinc-300">
                        Persona de contacto
                        <input class="field mt-2" name="contact_name" value="{{ old('contact_name', $supplier->contact_name) }}" maxlength="120" placeholder="Ej. Jorge Navarro">
                    </label>
                    <label class="text-xs font-bold text-zinc-300">
                        WhatsApp (Perú +51)
                        @php
                            $supPhoneDigits = $supplier->whatsappDigits();
                            if (strlen($supPhoneDigits) === 9 && str_starts_with($supPhoneDigits, '9')) {
                                $supPhoneDigits = '51'.$supPhoneDigits;
                            }
                            $supPhoneVal = old('phone') !== null
                                ? (string) old('phone')
                                : ($supPhoneDigits !== '' ? '+'.$supPhoneDigits : '+51');
                        @endphp
                        <input class="field font-mono-num mt-2"
                               name="phone"
                               type="tel"
                               value="{{ $supPhoneVal }}"
                               maxlength="12"
                               pattern="\+519[0-9]{8}"
                               inputmode="numeric"
                               data-peru-phone="required"
                               title="Solo números de Perú: +51 seguido de 9 dígitos"
                               placeholder="+51999110022"
                               autocomplete="tel"
                               required
                               data-wa-input>
                        <span class="mt-1.5 flex items-center gap-1.5 text-[10px] font-medium text-emerald-300/80">
                            <x-icon name="whatsapp" class="size-3" />
                            <span>Enlace directo: <code data-wa-preview class="font-mono-num">wa.me/{{ $supplier->whatsappDigits() ?: '519...' }}</code></span>
                        </span>
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="text-xs font-bold text-zinc-300">
                        Correo electrónico (opcional)
                        <input class="field mt-2" name="email" type="email" value="{{ old('email', $supplier->email) }}" placeholder="ventas@proveedor.pe">
                    </label>
                    <label class="text-xs font-bold text-zinc-300">
                        Dirección o zona de despacho (opcional)
                        <input class="field mt-2" name="address" value="{{ old('address', $supplier->address) }}" maxlength="255" placeholder="Ej. Av. Industrial 420 · Reparto Mar/Vie">
                    </label>
                </div>

                <div class="rounded-2xl border border-emerald-400/15 bg-emerald-400/[.03] p-4">
                    <label class="block text-xs font-bold text-emerald-200">
                        Plantilla de WhatsApp predeterminada
                        <select class="field mt-2 text-xs" name="whatsapp_template" data-wa-tpl-select>
                            <option value="">Usar predeterminada de la cartera</option>
                            @foreach(\App\Models\Supplier::WHATSAPP_TEMPLATES as $key => $tpl)
                                <option value="{{ $key }}" data-tpl-preview="{{ $tpl['message'] }}"
                                    @selected(old('whatsapp_template', $supplier->whatsapp_template) === $key)>
                                    {{ $tpl['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <p data-wa-tpl-preview class="mt-2.5 text-[11px] leading-5 text-zinc-400">
                        Al presionar WhatsApp en la ficha del proveedor, se abrirá el selector de mensajes con esta plantilla preseleccionada antes de enviar.
                    </p>
                </div>
            </div>

            {{-- Bloque 3: Notas & Espacio reservado Productos --}}
            <div class="space-y-4 border-t border-white/[.06] pt-5">
                <label class="block text-xs font-bold text-zinc-300">
                    Notas operativas (días de despacho, pedidos mínimos, observaciones)
                    <textarea class="field mt-2 min-h-24" name="notes" maxlength="2000" placeholder="Ej. Reparto martes y viernes sin recargo en compras mayores a S/ 300.">{{ old('notes', $supplier->notes) }}</textarea>
                </label>

                <div class="rounded-2xl border border-dashed border-fuchsia-400/25 bg-fuchsia-500/[.04] p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <span class="grid size-8 place-items-center rounded-xl bg-fuchsia-500/15 text-fuchsia-300">
                                <x-icon name="package" class="size-4" />
                            </span>
                            <div>
                                <p class="text-xs font-extrabold text-fuchsia-200">Conexión futura con módulo de Productos</p>
                                <p class="text-[11px] text-zinc-400">Cuando el módulo de Productos esté integrado, aquí podrás asociar productos abastecidos directamente a este proveedor.</p>
                            </div>
                        </div>
                        <span class="rounded-full border border-fuchsia-400/25 bg-fuchsia-500/10 px-2.5 py-1 text-[10px] font-bold text-fuchsia-200">Espacio reservado</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                <button class="btn-primary text-xs">
                    <x-icon name="check-circle" class="size-4" />
                    <span>{{ $supplier->exists ? 'Guardar cambios' : 'Guardar proveedor' }}</span>
                </button>
                <a href="{{ route('admin.suppliers.index') }}" class="btn-subtle text-xs">Cancelar</a>
            </div>
        </form>
    </div>

    <script>
        document.querySelectorAll('[data-wa-input]').forEach((input) => {
            const preview = document.querySelector('[data-wa-preview]');
            if (!preview) return;
            const sync = () => {
                const digits = (input.value || '').replace(/\D+/g, '');
                preview.textContent = `wa.me/${digits || '519...'}`;
            };
            input.addEventListener('input', sync);
            sync();
        });

        const tplSelect = document.querySelector('[data-wa-tpl-select]');
        const tplPreview = document.querySelector('[data-wa-tpl-preview]');
        if (tplSelect && tplPreview) {
            const nameInput = document.querySelector('input[name="name"]');
            const syncTpl = () => {
                const opt = tplSelect.selectedOptions?.[0];
                const raw = opt?.dataset?.tplPreview;
                if (!raw) {
                    tplPreview.textContent = 'Al presionar WhatsApp en la ficha del proveedor, se abrirá el selector de mensajes con la plantilla predeterminada de tu cartera.';
                    return;
                }
                const supplierName = (nameInput?.value || '').trim() || 'Proveedor';
                tplPreview.textContent = `Vista previa: "${raw.replace(':supplier', supplierName)}"`;
            };
            tplSelect.addEventListener('change', syncTpl);
            nameInput?.addEventListener('input', syncTpl);
            syncTpl();
        }
    </script>
@endsection
