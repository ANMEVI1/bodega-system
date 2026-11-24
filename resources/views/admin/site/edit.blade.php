@extends('layouts.admin')
@section('title', 'Editar página web')
@section('heading', 'Editor dinámico de página web')
@section('content')
    @php
        $sectionMeta = [
            'home' => [
                'badge' => 'Portada izquierda',
                'label' => 'Portada principal',
                'target' => 'Encabezado gigante (H1), subtítulo, botón principal y su destino en /',
                'preview' => 'hero',
            ],
            'banner' => [
                'badge' => 'Lámina derecha',
                'label' => 'Vitrina y lámina fotográfica',
                'target' => 'Pie editorial bajo la foto interactiva (o portada de respaldo si ocultas Portada)',
                'preview' => 'hero',
            ],
            'about' => [
                'badge' => 'Sección #nosotros',
                'label' => 'Sobre nosotros',
                'target' => 'Relato e identidad de la bodega en la web pública',
                'preview' => 'about',
            ],
            'faq' => [
                'badge' => 'Sección #preguntas',
                'label' => 'Preguntas frecuentes',
                'target' => 'Acordeón desplegable automático en formato ¿Pregunta? Respuesta',
                'preview' => 'faq',
            ],
            'contact' => [
                'badge' => 'Sección #contacto + Barra',
                'label' => 'Contacto y WhatsApp',
                'target' => 'Bloque final, botón de formulario, barra superior y redirección WhatsApp',
                'preview' => 'contact',
            ],
        ];
        $activeCount = $sections->filter(fn ($s) => (bool) old("sections.{$s->section}.active", $s->active))->count();
        $initialWhatsapp = old('sections.contact.whatsapp', $sections['contact']->whatsapp);
        $initialWhatsappDigits = $initialWhatsapp ? preg_replace('/\D+/', '', (string) $initialWhatsapp) : '';
    @endphp

    <div data-cinema-enter class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.18em] text-fuchsia-300/90">Presencia digital sincronizada</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Tu vitrina pública en vivo</h1>
            <p class="mt-1 text-xs text-zinc-400">Edita cada bloque de la web principal, controla su visibilidad y valida en tiempo real cómo se ve antes de guardar.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('admin.contacts.index') }}" class="btn-subtle text-xs">
                <x-icon name="message" class="size-4 text-fuchsia-300" />
                <span>Consultas web ({{ $contactStats['total'] }})</span>
                @if($contactStats['new'] > 0)
                    <span class="rounded-full bg-fuchsia-500/25 px-2 py-0.5 text-[10px] font-extrabold text-fuchsia-200">{{ $contactStats['new'] }} nuevas</span>
                @endif
            </a>
            <a href="{{ route('admin.products.index') }}" class="btn-subtle text-xs">
                <x-icon name="shopping-bag" class="size-4 text-emerald-300" />
                <span>Productos públicos ({{ $publicProducts->count() }})</span>
            </a>
            <a target="_blank" rel="noopener noreferrer" href="{{ route('home') }}" class="btn-subtle text-xs">
                <x-icon name="store" class="size-4 text-amber-300" />
                <span>Abrir sitio público</span>
                <x-icon name="arrow-up-right" class="size-3.5" />
            </a>
        </div>
    </div>

    {{-- KPIs de sincronización real con el Home --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="panel spotlight-card rounded-3xl p-5">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-bold uppercase tracking-[.14em] text-zinc-400">Bloques visibles</p>
                <span class="grid size-8 place-items-center rounded-xl bg-fuchsia-500/15 text-fuchsia-300">
                    <x-icon name="globe" class="size-4" />
                </span>
            </div>
            <p class="font-mono-num mt-3 text-2xl font-black text-white">
                <span data-preview-active-count>{{ $activeCount }}</span> / {{ $sections->count() }}
            </p>
            <p class="mt-1 text-[11px] text-zinc-400">Secciones activas en la portada</p>
        </div>

        <div class="panel spotlight-card rounded-3xl p-5">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-bold uppercase tracking-[.14em] text-zinc-400">Catálogo automático</p>
                <span class="grid size-8 place-items-center rounded-xl bg-emerald-500/15 text-emerald-300">
                    <x-icon name="package" class="size-4" />
                </span>
            </div>
            <p class="font-mono-num mt-3 text-2xl font-black text-white">{{ $publicProducts->count() }}</p>
            <p class="mt-1 text-[11px] text-zinc-400">
                {{ $publicCategories->count() }} {{ $publicCategories->count() === 1 ? 'categoría visible' : 'categorías visibles' }} desde Productos
            </p>
        </div>

        <div class="panel spotlight-card rounded-3xl p-5">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-bold uppercase tracking-[.14em] text-zinc-400">Destacado del día</p>
                <span class="grid size-8 place-items-center rounded-xl bg-amber-500/15 text-amber-300">
                    <x-icon name="sparkles" class="size-4" />
                </span>
            </div>
            @if($featuredProduct)
                <p class="mt-3 truncate text-base font-extrabold text-white">{{ $featuredProduct->name }}</p>
                <p class="font-mono-num mt-1 text-xs font-bold text-amber-300">S/ {{ number_format((float) $featuredProduct->selling_price, 2) }} · Automático en portada</p>
            @else
                <p class="mt-3 text-base font-bold text-zinc-300">Sin artículos públicos</p>
                <p class="mt-1 text-[11px] text-zinc-400">Marca un producto como público con stock</p>
            @endif
        </div>

        <div class="panel spotlight-card rounded-3xl p-5">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-bold uppercase tracking-[.14em] text-zinc-400">Canal WhatsApp</p>
                <span class="grid size-8 place-items-center rounded-xl bg-emerald-500/15 text-emerald-300">
                    <x-icon name="whatsapp" class="size-4" />
                </span>
            </div>
            <p data-preview-wa-badge class="font-mono-num mt-3 truncate text-base font-extrabold {{ strlen($initialWhatsappDigits) >= 8 ? 'text-emerald-300' : 'text-amber-300' }}">
                {{ strlen($initialWhatsappDigits) >= 8 ? '+'.$initialWhatsappDigits : 'Sin configurar' }}
            </p>
            <p class="mt-1 text-[11px] text-zinc-400">Barra superior y redirección del formulario</p>
        </div>
    </div>

    <div data-site-editor data-defaults="{{ json_encode($defaults) }}" class="mt-6 grid items-start gap-6 xl:grid-cols-[1.08fr_.92fr]">
        {{-- COLUMNA IZQUIERDA: FORMULARIO ESTRUCTURADO POR SECCIONES --}}
        <form id="site-editor-form" method="POST" action="{{ route('admin.site.update') }}" class="space-y-4">
            @csrf @method('PUT')

            {{-- Barra de filtro rápido de secciones + estado de cambios --}}
            <div class="panel flex flex-wrap items-center justify-between gap-3 rounded-2xl px-4 py-3">
                <div class="flex flex-wrap items-center gap-1.5" role="tablist" aria-label="Filtrar bloque editorial">
                    <button type="button" data-section-tab="all" aria-selected="true"
                        class="rounded-full border border-fuchsia-400/40 bg-fuchsia-500/20 px-3 py-1.5 text-[11px] font-bold text-fuchsia-100 transition">
                        Todos (5)
                    </button>
                    @foreach($sections as $key => $section)
                        @php $meta = $sectionMeta[$key] ?? ['label' => $key]; @endphp
                        <button type="button" data-section-tab="{{ $key }}" aria-selected="false"
                            class="rounded-full border border-white/10 bg-white/3 px-3 py-1.5 text-[11px] font-semibold text-zinc-400 transition hover:border-white/25 hover:text-zinc-200">
                            {{ $meta['label'] }}
                        </button>
                    @endforeach
                </div>

                <span data-editor-dirty-badge class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/3 px-3 py-1 text-[11px] font-semibold text-zinc-400">
                    <span class="size-1.5 rounded-full bg-emerald-400" data-dirty-dot></span>
                    <span data-dirty-text>Sincronizado</span>
                </span>
            </div>

            <div class="flex items-center justify-between gap-3 rounded-2xl border border-emerald-400/15 bg-emerald-500/4 px-4 py-2.5 text-[11px] text-emerald-200">
                <div class="flex items-center gap-2">
                    <x-icon name="sparkles" class="size-4 shrink-0 text-emerald-300" />
                    <span><strong>Chatbot de tienda sincronizado:</strong> el asistente público de <code class="text-emerald-100">/</code> usa automáticamente estos textos, preguntas frecuentes y tu catálogo público sin exponer datos internos.</span>
                </div>
            </div>

            @foreach($sections as $key => $section)
                @php
                    $meta = $sectionMeta[$key] ?? ['badge' => $key, 'label' => $key, 'target' => '', 'preview' => 'hero'];
                    $isActive = (bool) old("sections.$key.active", $section->active);
                    $titleValue = old("sections.$key.title", $section->title);
                    $contentValue = old("sections.$key.content", $section->content);
                    $ctaLabelValue = old("sections.$key.cta_label", $section->cta_label);
                    $ctaUrlValue = old("sections.$key.cta_url", $section->cta_url);
                @endphp

                <section data-section-card="{{ $key }}" data-preview-jump="{{ $meta['preview'] }}"
                    class="panel spotlight-card rounded-3xl p-5 transition sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-white/[.07] pb-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full border border-fuchsia-400/25 bg-fuchsia-500/12 px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-[.16em] text-fuchsia-200">
                                    {{ $meta['badge'] }}
                                </span>
                                <span class="text-[11px] font-medium text-zinc-400">{{ $meta['target'] }}</span>
                            </div>
                            <h2 class="mt-2 text-lg font-extrabold text-white">{{ $meta['label'] }}</h2>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" data-reset-section="{{ $key }}"
                                class="rounded-full border border-white/10 bg-white/3 px-3 py-1.5 text-[11px] font-semibold text-zinc-400 transition hover:border-white/25 hover:text-zinc-100"
                                title="Restaurar texto sugerido para este bloque">
                                Restaurar
                            </button>

                            <label class="inline-flex cursor-pointer items-center gap-2.5 rounded-full border border-white/12 bg-white/4 px-3 py-1.5 text-xs font-semibold text-zinc-200 transition hover:border-white/25">
                                <input type="hidden" name="sections[{{ $key }}][active]" value="0">
                                <input
                                    type="checkbox"
                                    name="sections[{{ $key }}][active]"
                                    value="1"
                                    data-section-input="{{ $key }}"
                                    data-field-role="active"
                                    class="peer sr-only"
                                    @checked($isActive)
                                >
                                <span class="relative inline-flex h-5 w-9 shrink-0 rounded-full bg-zinc-700 transition peer-checked:bg-emerald-500 after:absolute after:left-0.5 after:top-0.5 after:size-4 after:rounded-full after:bg-white after:transition-all peer-checked:after:translate-x-4"></span>
                                <span data-visibility-label="{{ $key }}" class="{{ $isActive ? 'text-emerald-300' : 'text-zinc-400' }}">
                                    {{ $isActive ? 'Visible en el sitio' : 'Oculto' }}
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4">
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <label for="section-{{ $key }}-title" class="text-xs font-bold text-zinc-300">Título del bloque</label>
                                <span class="font-mono-num text-[10px] text-zinc-500" data-char-count="section-{{ $key }}-title">{{ mb_strlen((string) $titleValue) }}/160</span>
                            </div>
                            <input
                                id="section-{{ $key }}-title"
                                class="field mt-1.5"
                                name="sections[{{ $key }}][title]"
                                value="{{ $titleValue }}"
                                maxlength="160"
                                data-section-input="{{ $key }}"
                                data-field-role="title"
                                data-char-target="section-{{ $key }}-title"
                                required
                            >
                        </div>

                        @if($key === 'faq')
                            {{-- Constructor visual estructurado para Preguntas Frecuentes --}}
                            <div>
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <span class="text-xs font-bold text-zinc-300">Preguntas y respuestas interactivas</span>
                                        <p class="text-[11px] text-zinc-400">Cada fila crea un desplegable ordenado en la sección Preguntas frecuentes del sitio.</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" data-faq-toggle-raw class="rounded-full border border-white/10 bg-white/3 px-3 py-1 text-[11px] font-semibold text-zinc-400 transition hover:border-white/25 hover:text-zinc-100">
                                            Modo texto
                                        </button>
                                        <button type="button" data-faq-add class="inline-flex items-center gap-1 rounded-full border border-fuchsia-400/35 bg-fuchsia-500/15 px-3 py-1 text-[11px] font-bold text-fuchsia-200 transition hover:bg-fuchsia-500/25">
                                            <x-icon name="plus" class="size-3.5" />
                                            <span>Agregar pregunta</span>
                                        </button>
                                    </div>
                                </div>

                                <div data-faq-builder class="mt-3 space-y-2.5"></div>

                                <div data-faq-raw-wrap class="mt-3 hidden">
                                    <textarea
                                        id="section-faq-content"
                                        class="field min-h-32 font-mono text-xs leading-6"
                                        name="sections[faq][content]"
                                        maxlength="4000"
                                        data-section-input="faq"
                                        data-field-role="content"
                                        placeholder="¿Cuál es su horario? Atendemos todos los días de 7:00 a.m. a 10:00 p.m."
                                    >{{ $contentValue }}</textarea>
                                    <p class="mt-1.5 text-[11px] text-zinc-500">Formato: una pregunta por línea terminada en signo de cierre <code class="text-zinc-300">?</code> seguida de su respuesta.</p>
                                </div>
                            </div>
                        @else
                            <div>
                                <div class="flex items-center justify-between gap-2">
                                    <label for="section-{{ $key }}-content" class="text-xs font-bold text-zinc-300">Texto descriptivo</label>
                                    <span class="font-mono-num text-[10px] text-zinc-500" data-char-count="section-{{ $key }}-content">{{ mb_strlen((string) $contentValue) }}/4000</span>
                                </div>
                                <textarea
                                    id="section-{{ $key }}-content"
                                    class="field mt-1.5 min-h-24"
                                    name="sections[{{ $key }}][content]"
                                    maxlength="4000"
                                    data-section-input="{{ $key }}"
                                    data-field-role="content"
                                    data-char-target="section-{{ $key }}-content"
                                >{{ $contentValue }}</textarea>
                            </div>
                        @endif

                        @if(in_array($key, ['home', 'banner', 'contact'], true))
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="section-{{ $key }}-cta-label" class="text-xs font-bold text-zinc-300">
                                        {{ $key === 'contact' ? 'Texto del botón de envío' : ($key === 'banner' ? 'Enlace opcional en la lámina' : 'Texto del botón principal') }}
                                    </label>
                                    <input
                                        id="section-{{ $key }}-cta-label"
                                        class="field mt-1.5"
                                        name="sections[{{ $key }}][cta_label]"
                                        value="{{ $ctaLabelValue }}"
                                        maxlength="80"
                                        data-section-input="{{ $key }}"
                                        data-field-role="cta_label"
                                        placeholder="{{ $key === 'contact' ? 'Enviar consulta' : 'Explorar catálogo' }}"
                                    >
                                </div>

                                @if(in_array($key, ['home', 'banner'], true))
                                    <div>
                                        <label for="section-{{ $key }}-cta-url" class="text-xs font-bold text-zinc-300">Destino al hacer clic</label>
                                        <input
                                            id="section-{{ $key }}-cta-url"
                                            class="field font-mono-num mt-1.5 text-xs"
                                            type="text"
                                            name="sections[{{ $key }}][cta_url]"
                                            value="{{ $ctaUrlValue }}"
                                            maxlength="255"
                                            data-section-input="{{ $key }}"
                                            data-field-role="cta_url"
                                            placeholder="#productos o https://…"
                                        >
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            @foreach([
                                                ['#productos', 'Catálogo'],
                                                ['#contacto', 'Contacto'],
                                                ['#nosotros', 'Nosotros'],
                                                ['#preguntas', 'Preguntas'],
                                            ] as [$presetUrl, $presetLabel])
                                                <button type="button" data-cta-preset="{{ $presetUrl }}" data-cta-target="section-{{ $key }}-cta-url"
                                                    class="rounded-full border border-white/10 bg-white/3 px-2.5 py-0.5 text-[10px] font-semibold text-zinc-400 transition hover:border-fuchsia-400/40 hover:text-fuchsia-200">
                                                    {{ $presetLabel }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if($key === 'contact')
                            <div class="rounded-2xl border border-emerald-400/15 bg-emerald-500/4 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <label for="section-contact-whatsapp" class="inline-flex items-center gap-2 text-xs font-bold text-emerald-200">
                                        <x-icon name="whatsapp" class="size-4 text-emerald-400" />
                                        <span>WhatsApp del negocio (código de país + número)</span>
                                    </label>
                                    <a
                                        href="{{ strlen($initialWhatsappDigits) >= 8 ? 'https://wa.me/'.$initialWhatsappDigits : '#' }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        data-wa-test-link
                                        class="inline-flex items-center gap-1 rounded-full border border-emerald-400/25 bg-emerald-500/10 px-2.5 py-1 text-[10px] font-bold text-emerald-200 transition hover:bg-emerald-500/20 {{ strlen($initialWhatsappDigits) >= 8 ? '' : 'pointer-events-none opacity-40' }}"
                                    >
                                        <span>Probar enlace wa.me</span>
                                        <x-icon name="arrow-up-right" class="size-3" />
                                    </a>
                                </div>

                                <input
                                    id="section-contact-whatsapp"
                                    class="field font-mono-num mt-2"
                                    name="sections[contact][whatsapp]"
                                    type="tel"
                                    value="{{ old('sections.contact.whatsapp') !== null ? (string) old('sections.contact.whatsapp') : (strlen($initialWhatsappDigits) > 2 ? '+'.$initialWhatsappDigits : '+51') }}"
                                    maxlength="12"
                                    pattern="(\+51|\+519[0-9]{8})?"
                                    inputmode="numeric"
                                    data-section-input="contact"
                                    data-field-role="whatsapp"
                                    data-peru-phone="optional"
                                    title="Solo números de Perú: +51 seguido de 9 dígitos"
                                    placeholder="+51987654321"
                                >
                                <p data-wa-helper class="mt-2 text-[11px] leading-5 text-zinc-400">
                                    Conecta directamente el botón <strong class="text-zinc-200">WhatsApp</strong> de la barra superior, el bloque de contacto y la redirección automática tras enviar una consulta. Si lo dejas vacío, el formulario solo registra la consulta en el panel.
                                </p>
                            </div>
                        @endif
                    </div>
                </section>
            @endforeach

            <div class="sticky bottom-20 z-20 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-white/10 bg-[#0b0b12]/92 px-4 py-3 shadow-2xl shadow-black/70 backdrop-blur-xl lg:bottom-5">
                <p class="text-xs text-zinc-400">
                    Los cambios se aplican al instante en <span class="font-mono-num text-zinc-200">/</span> al guardar.
                </p>
                <button type="submit" class="btn-primary">
                    <x-icon name="check-circle" class="size-4" />
                    <span>Guardar cambios del sitio</span>
                </button>
            </div>
        </form>

        {{-- COLUMNA DERECHA: VISTA PREVIA DINÁMICA EN VIVO --}}
        <aside class="panel overflow-hidden rounded-3xl xl:sticky xl:top-20" aria-label="Vista previa en tiempo real">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/8 bg-[#0a0a10] px-4 py-3">
                <div class="flex items-center gap-2">
                    <span class="size-2 rounded-full bg-emerald-400"></span>
                    <span class="text-xs font-extrabold text-white">Vista previa en vivo</span>
                    <span class="hidden text-[11px] text-zinc-500 sm:inline">· Web principal</span>
                </div>

                <div class="flex items-center gap-1 rounded-full border border-white/10 bg-white/3 p-1">
                    <button type="button" data-preview-mode="desktop" aria-pressed="true"
                        class="rounded-full bg-fuchsia-500/25 px-2.5 py-1 text-[10px] font-bold text-fuchsia-100 transition">
                        Escritorio
                    </button>
                    <button type="button" data-preview-mode="mobile" aria-pressed="false"
                        class="rounded-full px-2.5 py-1 text-[10px] font-bold text-zinc-400 transition hover:text-zinc-200">
                        Móvil
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap gap-1.5 border-b border-white/6 bg-[#09090f] px-4 py-2 text-[10px]">
                @foreach([
                    ['hero', 'Portada y lámina'],
                    ['about', 'Nosotros'],
                    ['catalog', 'Catálogo'],
                    ['faq', 'Preguntas'],
                    ['contact', 'Contacto'],
                ] as [$anchor, $label])
                    <button type="button" data-preview-scroll="{{ $anchor }}"
                        class="rounded-md border border-white/8 bg-white/2 px-2 py-1 font-semibold text-zinc-400 transition hover:border-white/20 hover:text-white">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="max-h-[calc(100vh-12.5rem)] overflow-y-auto bg-[#07070b] p-4">
                <div data-site-preview class="mx-auto w-full overflow-hidden rounded-sm border border-[#192315]/25 bg-[#F4EFE4] text-[#192315] shadow-2xl transition-all duration-300">
                    {{-- Mini Masthead --}}
                    <div class="flex items-center justify-between gap-2 border-b border-[#192315]/12 bg-[#F4EFE4] px-4 py-2.5">
                        <div class="flex items-center gap-2">
                            <span class="grid size-6 place-items-center rounded-xs bg-[#192315] text-[#F4EFE4]">
                                <x-icon name="sprout" class="size-3.5" />
                            </span>
                            <div>
                                <p class="font-serif text-sm font-semibold leading-none text-[#192315]">Bodega Los Olivos</p>
                                <p class="text-[8px] font-semibold uppercase tracking-[0.14em] text-[#595548]">Despensa de barrio</p>
                            </div>
                        </div>
                        <span data-pv-header-wa class="rounded-xs bg-[#192315] px-2.5 py-1 text-[9px] font-semibold text-[#F4EFE4]">
                            {{ strlen($initialWhatsappDigits) >= 8 ? 'WhatsApp' : 'Contacto' }}
                        </span>
                    </div>

                    {{-- Preview 1: Hero & Banner --}}
                    <div data-pv-block="hero" class="border-b border-[#192315]/14 px-4 py-5">
                        <div data-pv-hero-empty class="hidden border border-dashed border-[#192315]/25 bg-[#FAF7EF] px-4 py-6 text-center text-[11px] text-[#595548]">
                            Portada principal y Lámina fotográfica están ocultas. Activa al menos una para mostrar la cabecera editorial.
                        </div>

                        <div data-pv-hero-grid class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-[8px] font-bold uppercase tracking-[0.18em] text-[#A44A28]">Bodega Los Olivos · Atención diaria</p>
                                <h3 data-pv-hero-title class="font-serif mt-2 text-2xl font-medium leading-[1.05] text-[#192315]">
                                    {{ ($sections['home']->active ? $sections['home'] : $sections['banner'])->title }}
                                </h3>
                                <p data-pv-hero-content class="mt-2 whitespace-pre-line text-[11px] leading-4 text-[#4C493E]">
                                    {{ ($sections['home']->active ? $sections['home'] : $sections['banner'])->content }}
                                </p>
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <span data-pv-hero-cta class="inline-flex items-center gap-1 rounded-xs bg-[#192315] px-3 py-1.5 text-[10px] font-semibold text-[#F4EFE4]">
                                        {{ ($sections['home']->active ? $sections['home'] : $sections['banner'])->cta_label ?: 'Explorar catálogo' }}
                                    </span>
                                    <span data-pv-hero-url class="font-mono-num text-[9px] text-[#595548]">
                                        → {{ ($sections['home']->active ? $sections['home'] : $sections['banner'])->cta_url ?: '#productos' }}
                                    </span>
                                </div>
                            </div>

                            <figure data-pv-banner-box class="border border-[#192315]/18 bg-[#FAF7EF]">
                                <div class="aspect-16/10 w-full overflow-hidden bg-[#192315]">
                                    <img src="{{ asset('images/bodega-peru.jpg') }}" alt="Vista previa de bodega peruana" class="h-full w-full object-cover">
                                </div>
                                <figcaption class="border-t border-[#192315]/12 px-3 py-2">
                                    <p data-pv-banner-title class="font-serif text-xs font-medium text-[#192315]">{{ $sections['banner']->title }}</p>
                                    <p data-pv-banner-content class="mt-0.5 text-[10px] leading-4 text-[#595548]">{{ $sections['banner']->content }}</p>
                                    <div data-pv-banner-cta-wrap class="mt-1.5 {{ filled($sections['banner']->cta_label) ? '' : 'hidden' }}">
                                        <span data-pv-banner-cta class="inline-block border-b border-[#A44A28] text-[9px] font-semibold text-[#A44A28]">
                                            {{ $sections['banner']->cta_label }} →
                                        </span>
                                    </div>
                                </figcaption>
                            </figure>
                        </div>
                    </div>

                    {{-- Preview 2: About --}}
                    <div data-pv-block="about" class="border-b border-[#192315]/14 px-4 py-4">
                        <div data-pv-section-off="about" class="{{ $sections['about']->active ? 'hidden' : '' }} border border-dashed border-[#192315]/25 bg-[#FAF7EF] px-3 py-2 text-[10px] font-medium text-[#595548]">
                            Sección «Sobre nosotros» oculta en el sitio público.
                        </div>
                        <div data-pv-section-on="about" class="{{ $sections['about']->active ? '' : 'hidden' }}">
                            <p class="text-[8px] font-bold uppercase tracking-[0.18em] text-[#A44A28]">01 · Sobre nosotros</p>
                            <h4 data-pv-about-title class="font-serif mt-1 text-lg font-medium text-[#192315]">{{ $sections['about']->title }}</h4>
                            <p data-pv-about-content class="mt-1.5 whitespace-pre-line font-serif text-xs leading-5 text-[#4C493E]">{{ $sections['about']->content }}</p>
                        </div>
                    </div>

                    {{-- Preview 3: Auto Catalog Snapshot --}}
                    <div data-pv-block="catalog" class="border-b border-[#192315]/14 bg-[#192315] px-4 py-4 text-[#F4EFE4]">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-[8px] font-bold uppercase tracking-[0.18em] text-[#D8B49A]">Sincronización automática · Catálogo</p>
                            <span class="font-mono-num text-[9px] text-[#EAE3D2]">{{ $publicProducts->count() }} publicados</span>
                        </div>
                        @if($publicProducts->isNotEmpty())
                            <div class="mt-2.5 grid grid-cols-2 gap-2">
                                @foreach($publicProducts->take(4) as $item)
                                    <div class="border border-white/15 bg-[#FAF7EF] p-2 text-[#192315]">
                                        <p class="truncate text-[9px] font-bold">{{ $item->name }}</p>
                                        <p class="font-mono-num mt-0.5 text-[10px] font-semibold text-[#A44A28]">S/ {{ number_format((float) $item->selling_price, 2) }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-2 text-[10px] text-[#D6D3C5]">Aún no hay productos marcados como públicos con stock disponible.</p>
                        @endif
                    </div>

                    {{-- Preview 4: FAQ --}}
                    <div data-pv-block="faq" class="border-b border-[#192315]/14 bg-[#EAE3D2] px-4 py-4">
                        <div data-pv-section-off="faq" class="{{ $sections['faq']->active ? 'hidden' : '' }} border border-dashed border-[#192315]/25 bg-[#FAF7EF] px-3 py-2 text-[10px] font-medium text-[#595548]">
                            Sección «Preguntas frecuentes» oculta en el sitio público.
                        </div>
                        <div data-pv-section-on="faq" class="{{ $sections['faq']->active ? '' : 'hidden' }}">
                            <p class="text-[8px] font-bold uppercase tracking-[0.18em] text-[#A44A28]">03 · Orientación</p>
                            <h4 data-pv-faq-title class="font-serif mt-1 text-lg font-medium text-[#192315]">{{ $sections['faq']->title }}</h4>
                            <div data-pv-faq-list class="mt-2.5 divide-y divide-[#192315]/14 border-y border-[#192315]/14"></div>
                        </div>
                    </div>

                    {{-- Preview 5: Contact --}}
                    <div data-pv-block="contact" class="px-4 py-4">
                        <div data-pv-section-off="contact" class="{{ $sections['contact']->active ? 'hidden' : '' }} border border-dashed border-[#192315]/25 bg-[#FAF7EF] px-3 py-2 text-[10px] font-medium text-[#595548]">
                            Sección «Contacto» oculta en el sitio público.
                        </div>
                        <div data-pv-section-on="contact" class="{{ $sections['contact']->active ? '' : 'hidden' }} grid gap-3 sm:grid-cols-2">
                            <div>
                                <p class="text-[8px] font-bold uppercase tracking-[0.18em] text-[#A44A28]">04 · Contacto directo</p>
                                <h4 data-pv-contact-title class="font-serif mt-1 text-lg font-medium text-[#192315]">{{ $sections['contact']->title }}</h4>
                                <p data-pv-contact-content class="mt-1 whitespace-pre-line text-[10px] leading-4 text-[#4C493E]">{{ $sections['contact']->content }}</p>
                                <div data-pv-wa-pill class="mt-2.5 {{ strlen($initialWhatsappDigits) >= 8 ? '' : 'hidden' }}">
                                    <span class="inline-flex items-center gap-1 rounded-xs bg-[#192315] px-2.5 py-1 text-[9px] font-semibold text-[#F4EFE4]">
                                        WhatsApp <span data-pv-wa-number class="font-mono-num">+{{ $initialWhatsappDigits }}</span>
                                    </span>
                                </div>
                            </div>
                            <div class="border border-[#192315]/18 bg-[#FAF7EF] p-3">
                                <p class="font-serif text-xs font-medium text-[#192315]">Envíanos tu consulta</p>
                                <div class="mt-2 space-y-1.5">
                                    <div class="h-5 border border-[#192315]/15 bg-white"></div>
                                    <div class="h-8 border border-[#192315]/15 bg-white"></div>
                                    <span data-pv-contact-cta class="inline-block rounded-xs bg-[#192315] px-2.5 py-1 text-[9px] font-semibold text-[#F4EFE4]">
                                        {{ $sections['contact']->cta_label ?: 'Enviar consulta' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
@endsection
