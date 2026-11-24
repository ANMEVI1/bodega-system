@extends('layouts.admin')
@section('title', $client->exists ? 'Editar cliente' : 'Agregar cliente')
@section('heading', $client->exists ? 'Editar cliente' : 'Nuevo cliente')
@section('content')
    @php
        $clientType = old('client_type', $client->client_type ?: 'person');
        $preferredContact = old('preferred_contact', $client->preferred_contact ?: 'whatsapp');
        $documentType = old('document_type', $client->document_type ?: '');
    @endphp

    <div class="mx-auto max-w-5xl" data-cinema-enter>
        <a href="{{ route('admin.clients.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-zinc-400 transition hover:text-white">
            <x-icon name="arrow-left" class="size-4" />
            Volver a clientes
        </a>

        <div class="mt-5 grid items-start gap-5 lg:grid-cols-[minmax(0,1.65fr)_minmax(260px,.75fr)]">
            <form method="POST"
                  action="{{ $client->exists ? route('admin.clients.update', $client) : route('admin.clients.store') }}"
                  class="panel rounded-3xl p-5 sm:p-7">
                @csrf @if($client->exists) @method('PUT') @endif

                <header class="border-b border-white/[.07] pb-5">
                    <p class="text-[11px] font-extrabold uppercase tracking-[.18em] text-fuchsia-300">Ficha comercial</p>
                    <h1 class="mt-1 text-2xl font-extrabold text-white">{{ $client->exists ? 'Editar cliente' : 'Nuevo cliente' }}</h1>
                    <p class="mt-2 text-xs leading-5 text-zinc-400">Organiza identificación, contacto y seguimiento en una sola ficha.</p>
                </header>

                @if($errors->any())
                    <div class="mt-5 rounded-2xl border border-rose-400/20 bg-rose-500/[.08] p-4" role="alert">
                        <p class="text-xs font-bold text-rose-200">Revisa estos campos antes de guardar:</p>
                        <ul class="mt-2 list-inside list-disc space-y-1 text-xs text-rose-100/90">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <section class="space-y-4 border-b border-white/[.07] py-5">
                    <div>
                        <h2 class="text-sm font-extrabold text-white">Identificación</h2>
                        <p class="mt-1 text-[11px] text-zinc-500">Los datos fiscales son opcionales y se guardan solo cuando los necesitas.</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block text-xs font-bold text-zinc-300">
                            Tipo de cliente
                            <select class="field mt-2" id="client-type" name="client_type" required>
                                <option value="person" @selected($clientType === 'person')>Persona</option>
                                <option value="business" @selected($clientType === 'business')>Empresa o negocio</option>
                            </select>
                            @error('client_type') <span class="mt-1 block text-[11px] font-medium text-rose-300">{{ $message }}</span> @enderror
                        </label>

                        <label class="block text-xs font-bold text-zinc-300">
                            <span id="client-name-label">{{ $clientType === 'business' ? 'Razón social' : 'Nombre completo' }}</span>
                            <input class="field mt-2" id="client-name" name="name" value="{{ old('name', $client->name) }}" maxlength="160" autocomplete="{{ $clientType === 'business' ? 'organization' : 'name' }}" required>
                            @error('name') <span class="mt-1 block text-[11px] font-medium text-rose-300">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block text-xs font-bold text-zinc-300">
                            Tipo de documento <span class="font-normal text-zinc-500">· obligatorio</span>
                            <select class="field mt-2" id="client-document-type" name="document_type" required>
                                <option value="" disabled @selected($documentType === '')>Selecciona un documento</option>
                                <option value="dni" data-document-for="person" @selected($documentType === 'dni')>DNI</option>
                                <option value="ce" data-document-for="person" @selected($documentType === 'ce')>Carné de extranjería</option>
                                <option value="passport" data-document-for="person" @selected($documentType === 'passport')>Pasaporte</option>
                                <option value="ruc" data-document-for="business" @selected($documentType === 'ruc')>RUC</option>
                            </select>
                            @error('document_type') <span class="mt-1 block text-[11px] font-medium text-rose-300">{{ $message }}</span> @enderror
                        </label>

                        <label class="block text-xs font-bold text-zinc-300">
                            Número de documento <span class="font-normal text-zinc-500">· obligatorio</span>
                            <input class="field mt-2"
                                   id="client-document-number"
                                   name="document_number"
                                   value="{{ old('document_number', $client->document_number) }}"
                                   maxlength="{{ $documentType === 'dni' ? 8 : ($documentType === 'ruc' ? 11 : 30) }}"
                                   pattern="{{ $documentType === 'dni' ? '[0-9]{8}' : ($documentType === 'ruc' ? '[0-9]{11}' : '[A-Za-z0-9]{1,30}') }}"
                                   inputmode="{{ in_array($documentType, ['dni', 'ruc'], true) ? 'numeric' : 'text' }}"
                                   data-numeric-only="{{ in_array($documentType, ['dni', 'ruc'], true) ? 'true' : 'false' }}"
                                   data-alphanumeric-only="{{ in_array($documentType, ['ce', 'passport'], true) ? 'true' : 'false' }}"
                                   required
                                   autocomplete="off">
                            @error('document_number') <span class="mt-1 block text-[11px] font-medium text-rose-300">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <div class="{{ $clientType === 'business' ? '' : 'hidden' }}" data-client-fields="business">
                        <label class="block text-xs font-bold text-zinc-300">
                            Persona de contacto <span class="font-normal text-zinc-500">· opcional</span>
                            <input class="field mt-2" name="contact_name" value="{{ old('contact_name', $client->contact_name) }}" maxlength="120" autocomplete="name" placeholder="Nombre de quien realiza los pedidos">
                            @error('contact_name') <span class="mt-1 block text-[11px] font-medium text-rose-300">{{ $message }}</span> @enderror
                        </label>
                    </div>
                </section>

                <section class="space-y-4 border-b border-white/[.07] py-5">
                    <div>
                        <h2 class="text-sm font-extrabold text-white">Contacto y atención</h2>
                        <p class="mt-1 text-[11px] text-zinc-500">El WhatsApp se usa para abrir mensajes listos para revisar y enviar.</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block text-xs font-bold text-zinc-300">
                            WhatsApp
                            <div class="mt-2 flex items-center gap-2">
                                <span class="font-mono-num inline-flex h-11 shrink-0 items-center rounded-2xl border border-white/10 bg-white/[.04] px-3 text-xs font-extrabold text-zinc-300">+</span>
                                <input class="field font-mono-num"
                                       name="phone"
                                       type="tel"
                                       value="{{ old('phone', $client->exists ? $client->whatsappPhone() : '51') }}"
                                       maxlength="15"
                                       pattern="(9[0-9]{8}|[1-9][0-9]{9,14})"
                                       inputmode="numeric"
                                       data-numeric-only="true"
                                       autocomplete="tel"
                                       placeholder="51912345678"
                                       required>
                            </div>
                            <span class="mt-1.5 block text-[11px] font-normal leading-5 text-zinc-500">Solo números (prefijo 51 preescrito para Perú: 519XXXXXXXX). Para otro país cambia el código numérico.</span>
                            @error('phone') <span class="mt-1 block text-[11px] font-medium text-rose-300">{{ $message }}</span> @enderror
                        </label>

                        <label class="block text-xs font-bold text-zinc-300">
                            Correo electrónico <span class="font-normal text-zinc-500">· opcional</span>
                            <input class="field mt-2" name="email" type="email" value="{{ old('email', $client->email) }}" maxlength="255" autocomplete="email" placeholder="cliente@correo.com">
                            <span class="mt-1.5 block text-[11px] font-normal leading-5 text-zinc-500">Necesario si eliges correo como canal preferido.</span>
                            @error('email') <span class="mt-1 block text-[11px] font-medium text-rose-300">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block text-xs font-bold text-zinc-300">
                            Canal de contacto preferido
                            <select class="field mt-2" name="preferred_contact" required>
                                <option value="whatsapp" @selected($preferredContact === 'whatsapp')>WhatsApp</option>
                                <option value="phone" @selected($preferredContact === 'phone')>Llamada telefónica</option>
                                <option value="email" @selected($preferredContact === 'email')>Correo electrónico</option>
                            </select>
                            @error('preferred_contact') <span class="mt-1 block text-[11px] font-medium text-rose-300">{{ $message }}</span> @enderror
                        </label>

                        <label class="block text-xs font-bold text-zinc-300">
                            Estado de la relación
                            <select class="field mt-2" name="status" required>
                                <option value="active" @selected(old('status', $client->status ?: 'active') === 'active')>Activo</option>
                                <option value="follow-up" @selected(old('status', $client->status) === 'follow-up')>En seguimiento</option>
                                <option value="inactive" @selected(old('status', $client->status) === 'inactive')>Inactivo</option>
                            </select>
                            @error('status') <span class="mt-1 block text-[11px] font-medium text-rose-300">{{ $message }}</span> @enderror
                        </label>
                    </div>
                </section>

                <section class="space-y-4 border-b border-white/[.07] py-5">
                    <div>
                        <h2 class="text-sm font-extrabold text-white">Ubicación y origen</h2>
                        <p class="mt-1 text-[11px] text-zinc-500">Información útil para entregas y para saber cómo llegó a la bodega.</p>
                    </div>

                    <label class="block text-xs font-bold text-zinc-300">
                        Dirección <span class="font-normal text-zinc-500">· opcional</span>
                        <input class="field mt-2" name="address" value="{{ old('address', $client->address) }}" maxlength="255" autocomplete="street-address" placeholder="Calle, avenida o referencia">
                        @error('address') <span class="mt-1 block text-[11px] font-medium text-rose-300">{{ $message }}</span> @enderror
                    </label>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block text-xs font-bold text-zinc-300">
                            Distrito
                            <input class="field mt-2" name="district" value="{{ old('district', $client->district) }}" maxlength="120" autocomplete="address-level3">
                            @error('district') <span class="mt-1 block text-[11px] font-medium text-rose-300">{{ $message }}</span> @enderror
                        </label>
                        <label class="block text-xs font-bold text-zinc-300">
                            Ciudad o provincia
                            <input class="field mt-2" name="city" value="{{ old('city', $client->city) }}" maxlength="120" autocomplete="address-level2">
                            @error('city') <span class="mt-1 block text-[11px] font-medium text-rose-300">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <label class="block text-xs font-bold text-zinc-300">
                        ¿Cómo conoció la bodega? <span class="font-normal text-zinc-500">· opcional</span>
                        <select class="field mt-2" name="source">
                            <option value="">Sin especificar</option>
                            <option value="walk_in" @selected(old('source', $client->source) === 'walk_in')>Visita a la tienda</option>
                            <option value="referral" @selected(old('source', $client->source) === 'referral')>Recomendación</option>
                            <option value="social" @selected(old('source', $client->source) === 'social')>Redes sociales</option>
                            <option value="whatsapp" @selected(old('source', $client->source) === 'whatsapp')>WhatsApp</option>
                            <option value="other" @selected(old('source', $client->source) === 'other')>Otro</option>
                        </select>
                        @error('source') <span class="mt-1 block text-[11px] font-medium text-rose-300">{{ $message }}</span> @enderror
                    </label>
                </section>

                <section class="space-y-4 py-5">
                    <div>
                        <h2 class="text-sm font-extrabold text-white">Notas internas</h2>
                        <p class="mt-1 text-[11px] text-zinc-500">Registra preferencias, indicaciones de entrega o próximos pasos.</p>
                    </div>
                    <label class="block text-xs font-bold text-zinc-300">
                        Notas <span class="font-normal text-zinc-500">· opcional</span>
                        <textarea class="field mt-2 min-h-28" name="notes" maxlength="2000" placeholder="Preferencias de compra, horarios de contacto o indicaciones importantes">{{ old('notes', $client->notes) }}</textarea>
                        @error('notes') <span class="mt-1 block text-[11px] font-medium text-rose-300">{{ $message }}</span> @enderror
                    </label>
                </section>

                <footer class="flex flex-wrap gap-3 border-t border-white/[.07] pt-5">
                    <button class="btn-primary text-xs">{{ $client->exists ? 'Guardar cambios' : 'Guardar cliente' }}</button>
                    <a href="{{ route('admin.clients.index') }}" class="btn-subtle text-xs">Cancelar</a>
                </footer>
            </form>

            <aside class="space-y-4">
                <section class="panel rounded-3xl p-5">
                    <p class="text-[11px] font-extrabold uppercase tracking-[.18em] text-violet-300">Ficha organizada</p>
                    <h2 class="mt-1 text-base font-extrabold text-white">Datos completos, sin campos obligatorios de más</h2>
                    <p class="mt-2 text-xs leading-5 text-zinc-400">Se solicita el documento correspondiente al tipo de cliente. Los demás datos adicionales se completan cuando aporten valor.</p>
                    <div class="mt-4 space-y-2 border-t border-white/[.07] pt-4 text-[11px] leading-5 text-zinc-500">
                        <p>Para personas puedes usar DNI, carné de extranjería o pasaporte; para empresas, RUC.</p>
                        <p>El canal preferido por correo requiere una dirección válida.</p>
                        <p>Las ventas asociadas construyen automáticamente el historial de productos.</p>
                    </div>
                </section>

                <section class="rounded-3xl border border-violet-400/15 bg-violet-400/[.06] p-5">
                    <p class="text-xs font-bold text-violet-100">Compras y productos</p>
                    <p class="mt-1 text-[11px] leading-5 text-zinc-400">El consumo se registra con las ventas asociadas al cliente, conservando producto, cantidad y fecha.</p>
                    @if($client->exists)
                        <a href="{{ route('admin.clients.show', $client) }}" class="mt-3 inline-flex items-center gap-1.5 text-xs font-bold text-fuchsia-200 hover:text-white">
                            Ver historial y registrar una venta
                            <x-icon name="arrow-right" class="size-3.5" />
                        </a>
                    @else
                        <p class="mt-2 text-[11px] text-zinc-500">Guarda el cliente para asociarlo a una venta.</p>
                    @endif
                </section>
            </aside>
        </div>
    </div>

    <script>
        const clientType = document.getElementById('client-type');
        const clientNameLabel = document.getElementById('client-name-label');
        const clientName = document.getElementById('client-name');
        const clientDocumentType = document.getElementById('client-document-type');
        const clientDocumentNumber = document.getElementById('client-document-number');
        const allowedDocumentTypes = {
            person: ['dni', 'ce', 'passport'],
            business: ['ruc'],
        };

        document.querySelectorAll('[data-numeric-only]').forEach((input) => {
            input.addEventListener('input', () => {
                if (input.dataset.numericOnly === 'true') {
                    input.value = input.value.replace(/\D/g, '');
                } else if (input.dataset.alphanumericOnly === 'true') {
                    input.value = input.value.replace(/[^A-Za-z0-9]/g, '');
                }
            });
        });

        function updateClientTypeFields() {
            const type = clientType.value;

            document.querySelectorAll('[data-client-fields]').forEach((fields) => {
                const visible = fields.dataset.clientFields === type;

                fields.classList.toggle('hidden', !visible);
                fields.querySelectorAll('input, select, textarea').forEach((input) => {
                    input.disabled = !visible;
                });
            });

            document.querySelectorAll('[data-document-for]').forEach((option) => {
                const visible = option.dataset.documentFor === type;

                option.hidden = !visible;
                option.disabled = !visible;
            });

            if (clientDocumentType.value && !allowedDocumentTypes[type].includes(clientDocumentType.value)) {
                clientDocumentType.value = '';
                clientDocumentNumber.value = '';
            }

            const business = type === 'business';
            clientNameLabel.textContent = business ? 'Razón social' : 'Nombre completo';
            clientName.autocomplete = business ? 'organization' : 'name';
            updateDocumentValidation();
        }

        function updateDocumentValidation() {
            const type = clientDocumentType.value;
            const numeric = type === 'dni' || type === 'ruc';
            const alphanumeric = type === 'ce' || type === 'passport';

            clientDocumentNumber.dataset.numericOnly = numeric ? 'true' : 'false';
            clientDocumentNumber.dataset.alphanumericOnly = alphanumeric ? 'true' : 'false';
            clientDocumentNumber.inputMode = numeric ? 'numeric' : 'text';
            clientDocumentNumber.maxLength = type === 'dni' ? 8 : (type === 'ruc' ? 11 : 30);
            clientDocumentNumber.pattern = type === 'dni'
                ? '[0-9]{8}'
                : (type === 'ruc' ? '[0-9]{11}' : '[A-Za-z0-9]{1,30}');
            clientDocumentNumber.required = true;
        }

        clientType.addEventListener('change', updateClientTypeFields);
        clientDocumentType.addEventListener('change', updateDocumentValidation);
        clientDocumentNumber.addEventListener('input', updateDocumentValidation);
        updateClientTypeFields();
        updateDocumentValidation();
    </script>
@endsection
