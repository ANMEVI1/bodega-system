@extends('layouts.site')
@section('content')
    @php
        $bannerSection = $sections['banner'];
        $hero = $sections['home']->active ? $sections['home'] : $bannerSection;
        $heroUrl = trim((string) ($hero->cta_url ?? '')) ?: '#productos';
        $heroExternal = preg_match('/^https?:\/\//i', $heroUrl) === 1;
        $bannerUrl = trim((string) ($bannerSection->cta_url ?? '')) ?: '#productos';
        $bannerExternal = preg_match('/^https?:\/\//i', $bannerUrl) === 1;
        $featuredProduct = $products->first();
        $whatsappDigits = $sections['contact']->whatsapp ? preg_replace('/\D+/', '', $sections['contact']->whatsapp) : null;
    @endphp
    <main class="relative z-10">
        {{-- 1. PORTADA EDITORIAL ASIMÉTRICA + LÁMINA INTERACTIVA DE LA BODEGA --}}
        @if($sections['home']->active || $bannerSection->active)
        <section data-hero-scene class="border-b border-[#192315]/14 bg-[#F4EFE4]/78">
            <div class="mx-auto grid max-w-7xl items-start gap-10 px-5 pb-14 pt-10 sm:px-8 sm:pb-20 sm:pt-14 {{ $bannerSection->active ? 'lg:grid-cols-[1.08fr_.92fr] lg:gap-14' : 'max-w-4xl' }} lg:py-20">
                {{-- Columna izquierda: Jerarquía tipográfica editorial --}}
                <div class="flex flex-col justify-between pt-1">
                    <div>
                        <p data-cinema-enter style="--enter-delay: 0" class="text-[11px] font-bold uppercase tracking-[0.2em] text-[#A44A28]">
                            Bodega Los Olivos · Atención diaria en tu barrio
                        </p>

                        <h1 data-cinema-enter style="--enter-delay: 1"
                            class="font-editorial mt-5 max-w-2xl text-[2.85rem] font-medium leading-[0.98] tracking-[-0.025em] text-[#192315] sm:text-[4rem] lg:text-[4.65rem]">
                            {{ $hero->title }}
                        </h1>

                        @if($hero->content)
                            <p data-cinema-enter style="--enter-delay: 2"
                               class="mt-6 max-w-xl whitespace-pre-line text-base leading-[1.7] text-[#4C493E] sm:text-lg">
                                {{ $hero->content }}
                            </p>
                        @endif

                        <div data-cinema-enter style="--enter-delay: 3" class="mt-8 flex flex-wrap items-center gap-3.5">
                            <a href="{{ $heroUrl }}" @if($heroExternal) target="_blank" rel="noopener noreferrer" @endif class="editorial-btn-primary">
                                <span>{{ $hero->cta_label ?: 'Explorar catálogo' }}</span>
                                <x-icon name="{{ $heroExternal ? 'arrow-up-right' : 'arrow-right' }}" class="size-4" />
                            </a>
                            <a href="#contacto" class="editorial-btn-secondary">
                                <x-icon name="message" class="size-4 text-[#A44A28]" />
                                <span>Hacer una consulta</span>
                            </a>
                        </div>
                    </div>

                    {{-- Cifras reales del catálogo actual, sin tarjetas ni rellenos --}}
                    <dl data-cinema-enter style="--enter-delay: 4"
                        class="mt-12 grid grid-cols-2 divide-x divide-[#192315]/14 border-y border-[#192315]/14 py-4 sm:grid-cols-3">
                        <div class="pr-4">
                            <dt class="text-[10px] font-bold uppercase tracking-[0.16em] text-[#595548]">En vitrina hoy</dt>
                            <dd class="font-mono-num mt-1.5 text-lg font-semibold text-[#192315]">
                                {{ $products->count() }} {{ $products->count() === 1 ? 'producto' : 'productos' }}
                            </dd>
                        </div>

                        <div class="px-4">
                            <dt class="text-[10px] font-bold uppercase tracking-[0.16em] text-[#595548]">Categorías</dt>
                            <dd class="font-mono-num mt-1.5 text-lg font-semibold text-[#192315]">
                                {{ $categories->count() > 0 ? $categories->count() : 'Despensa' }}
                            </dd>
                        </div>

                        <div class="col-span-2 mt-4 border-t border-[#192315]/10 pt-3 sm:col-span-1 sm:mt-0 sm:border-t-0 sm:pl-4 sm:pt-0">
                            <dt class="text-[10px] font-bold uppercase tracking-[0.16em] text-[#A44A28]">
                                {{ $featuredProduct ? 'Destacado del día' : 'Horario y reserva' }}
                            </dt>
                            <dd class="mt-1.5 flex items-baseline justify-between gap-2 text-sm font-semibold text-[#192315]">
                                @if($featuredProduct)
                                    <span class="truncate">{{ $featuredProduct->name }}</span>
                                    <span class="font-mono-num shrink-0 text-[#263822]">S/ {{ number_format((float) $featuredProduct->selling_price, 2) }}</span>
                                @else
                                    <span>Pedidos por WhatsApp</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>

                @if($bannerSection->active)
                {{-- Columna derecha: Fotografía viva de bodega peruana sin etiquetas encima --}}
                <figure data-cinema-enter style="--enter-delay: 2" data-bodega-stage class="border border-[#192315]/18 bg-[#FAF7EF]">
                    <div data-bodega-viewport class="bodega-viewport relative aspect-[5/4] w-full overflow-hidden bg-[#192315] sm:aspect-[16/12]">
                        <img
                            src="{{ asset('images/bodega-peru.jpg') }}"
                            alt="Interior de bodega peruana moderna con abarrotes, granos andinos, frutas frescas y pan del día"
                            draggable="false"
                            data-bodega-img
                            class="bodega-stage-media is-idle h-full w-full select-none object-cover"
                        >
                        <div class="pointer-events-none absolute inset-0 ring-1 ring-inset ring-black/10"></div>
                    </div>

                    {{-- Pie de fotografía + selector de zonas fuera de la imagen --}}
                    <figcaption class="border-t border-[#192315]/14 bg-[#FAF7EF] px-4 py-3.5 sm:px-5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-editorial text-base font-medium text-[#192315]">
                                {{ $bannerSection->title }}
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-[#595548]">
                                <svg class="size-3 shrink-0 text-[#A44A28]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <polyline points="5 9 2 12 5 15"/><polyline points="9 5 12 2 15 5"/><polyline points="15 19 12 22 9 19"/><polyline points="19 9 22 12 19 15"/><line x1="2" x2="22" y1="12" y2="12"/><line x1="12" x2="12" y1="2" y2="22"/>
                                </svg>
                                <span data-bodega-hint>Mueve o arrastra la escena</span>
                            </span>
                        </div>

                        @if($bannerSection->content)
                            <p class="mt-1.5 text-xs leading-5 text-[#595548]">{{ $bannerSection->content }}</p>
                        @endif

                        <div class="mt-3 flex flex-wrap items-center justify-between gap-3 border-t border-[#192315]/10 pt-2.5">
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5" role="group" aria-label="Explorar zonas de la bodega">
                                @foreach([
                                    ['general', '00 General', true],
                                    ['estantes', '01 Estantes', false],
                                    ['frescos', '02 Frescos', false],
                                    ['mostrador', '03 Mostrador', false],
                                ] as [$key, $label, $activePreset])
                                    <button
                                        type="button"
                                        data-bodega-focus="{{ $key }}"
                                        aria-pressed="{{ $activePreset ? 'true' : 'false' }}"
                                        class="font-mono-num py-1 text-[11px] font-semibold tracking-wide"
                                    >
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>

                            @if($sections['home']->active && filled($bannerSection->cta_label))
                                <a href="{{ $bannerUrl }}" @if($bannerExternal) target="_blank" rel="noopener noreferrer" @endif class="inline-flex items-center gap-1 border-b border-[#192315]/35 pb-0.5 text-[11px] font-semibold text-[#192315] transition-colors hover:border-[#A44A28] hover:text-[#A44A28]">
                                    <span>{{ $bannerSection->cta_label }}</span>
                                    <x-icon name="arrow-up-right" class="size-3" />
                                </a>
                            @endif
                        </div>
                    </figcaption>
                </figure>
                @endif
            </div>
        </section>
        @endif

        {{-- 2. IDENTIDAD Y OFICIO DE BARRIO (Pliego editorial con filetes) --}}
        @if($sections['about']->active)
        <section id="nosotros" class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:py-24">
            <div class="grid gap-10 lg:grid-cols-[.82fr_1.18fr] lg:gap-16" data-reveal>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-[#A44A28]">01 · Sobre nosotros</p>
                    <h2 class="font-editorial mt-4 text-4xl font-medium leading-[1.08] tracking-tight text-[#192315] sm:text-5xl">
                        {{ $sections['about']->title }}
                    </h2>
                </div>

                <div class="flex flex-col justify-center">
                    <p class="whitespace-pre-line font-editorial text-xl leading-[1.68] text-[#2D3327] sm:text-[1.35rem]">
                        {{ $sections['about']->content }}
                    </p>
                </div>
            </div>

            <div class="mt-14 grid divide-y divide-[#192315]/14 border-y border-[#192315]/14 md:grid-cols-3 md:divide-x md:divide-y-0">
                @foreach([
                    ['01', 'Abastecimiento cuidado', 'Trabajamos con proveedores seleccionados para sostener frescura y rotación diaria en cada categoría de tu hogar.'],
                    ['02', 'Precios transparentes', 'Control ordenado de costos y márgenes justos para ofrecer valor real todos los días, sin sorpresas en mostrador.'],
                    ['03', 'Atención cercana y ágil', 'Consulta nuestro stock publicado al instante y separa tu pedido directo por WhatsApp sin hacer largas colas.'],
                ] as [$num, $title, $desc])
                    <article data-reveal style="--stagger: {{ $loop->index }}" class="py-7 md:px-7 md:first:pl-0 md:last:pr-0">
                        <span class="font-mono-num text-xs font-semibold text-[#A44A28]">{{ $num }}</span>
                        <h3 class="font-editorial mt-3 text-2xl font-medium text-[#192315]">{{ $title }}</h3>
                        <p class="mt-2.5 text-sm leading-6 text-[#595548]">{{ $desc }}</p>
                    </article>
                @endforeach
            </div>
        </section>
        @endif

        {{-- 3. INTERLUDIO EDITORIAL EN TINTA VERDE BOSQUE + SELECCIÓN DESTACADA --}}
        <section class="relative overflow-hidden border-y border-[#192315]/15 bg-[#192315] text-[#F4EFE4]">
            <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
                <div data-parallax-y="0.08" class="absolute -inset-6">
                    <img
                        src="{{ asset('images/bodega-peru.jpg') }}"
                        alt=""
                        class="h-full w-full object-cover opacity-20"
                    >
                </div>
                <div class="absolute inset-0 bg-gradient-to-r from-[#192315]/96 via-[#192315]/86 to-[#192315]/92"></div>
            </div>

            <div class="relative z-10 mx-auto grid max-w-7xl items-center gap-10 px-5 py-16 sm:px-8 lg:grid-cols-[1.2fr_.8fr] lg:py-20">
                <div data-reveal>
                    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-[#D8B49A]">El ritmo de nuestra bodega</p>
                    <blockquote class="font-editorial mt-4 text-3xl font-normal leading-[1.28] text-[#F4EFE4] sm:text-4xl">
                        Desde el primer café de la mañana hasta la compra de último minuto, reunimos
                        <span class="italic text-[#D8B49A]">selección fresca</span>, precios claros y trato de barrio.
                    </blockquote>
                </div>

                @if($featuredProduct)
                    <aside data-reveal style="--stagger: 1" class="border border-[#F4EFE4]/20 bg-[#FAF7EF] p-6 text-[#192315] sm:p-7">
                        <div class="flex items-center justify-between gap-2 border-b border-[#192315]/12 pb-3">
                            <span class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#A44A28]">Selección recomendada</span>
                            <span class="font-mono-num text-[11px] font-semibold text-[#263822]">{{ number_format((float) $featuredProduct->stock, 0) }} {{ $featuredProduct->unit }} disp.</span>
                        </div>
                        <p class="mt-4 text-[10px] font-bold uppercase tracking-[0.16em] text-[#595548]">{{ $featuredProduct->category }}</p>
                        <h3 class="font-editorial mt-1 text-2xl font-medium text-[#192315]">{{ $featuredProduct->name }}</h3>
                        <div class="mt-5 flex items-end justify-between gap-4 border-t border-[#192315]/12 pt-4">
                            <div>
                                <span class="block text-[10px] uppercase tracking-[0.14em] text-[#595548]">Precio en tienda</span>
                                <span class="font-mono-num text-2xl font-semibold text-[#192315]">S/ {{ number_format((float) $featuredProduct->selling_price, 2) }}</span>
                            </div>
                            <button type="button" data-inquire-product="{{ $featuredProduct->name }}" class="editorial-btn-primary">
                                <span>Pedir</span>
                                <x-icon name="arrow-up-right" class="size-3.5" />
                            </button>
                        </div>
                    </aside>
                @else
                    <aside data-reveal style="--stagger: 1" class="border border-[#F4EFE4]/20 bg-[#F4EFE4]/8 p-6 backdrop-blur-sm sm:p-7">
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#D8B49A]">Atención personalizada</p>
                        <h3 class="font-editorial mt-2 text-2xl font-medium text-[#F4EFE4]">Consulta disponibilidad al instante</h3>
                        <p class="mt-2.5 text-sm leading-6 text-[#D6D3C5]">Pregúntanos por abarrotes, bebidas y encargos del día sin esperar.</p>
                        <a href="#contacto" class="mt-5 inline-flex items-center gap-2 border-b border-[#D8B49A] pb-1 text-xs font-semibold text-[#F4EFE4] transition-colors hover:text-[#D8B49A]">
                            <span>Ir al formulario de pedido</span>
                            <x-icon name="arrow-right" class="size-3.5" />
                        </a>
                    </aside>
                @endif
            </div>
        </section>

        {{-- 4. CATÁLOGO VIVO (Retícula limpia de despensa) --}}
        <section id="productos" class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:py-24">
            <div class="flex flex-wrap items-end justify-between gap-6 border-b border-[#192315]/14 pb-8" data-reveal>
                <div>
                    <h2 class="font-editorial text-4xl font-medium tracking-tight text-[#192315] sm:text-5xl">
                        Un vistazo a <span class="italic text-[#A44A28]">nuestra despensa</span>
                    </h2>
                    <p class="mt-2.5 text-sm text-[#595548]">
                        Precios claros actualizados directamente desde tienda. Selecciona un artículo para pedirlo por mensaje.
                    </p>
                </div>

                @if(isset($categories) && $categories->count() > 1)
                    <div class="flex flex-wrap gap-2" role="group" aria-label="Filtrar por categoría">
                        <button type="button" data-category-filter="all" aria-pressed="true" class="px-4 py-1.5 text-xs font-semibold">
                            Todos
                        </button>
                        @foreach($categories as $category)
                            <button type="button" data-category-filter="{{ $category }}" aria-pressed="false" class="px-4 py-1.5 text-xs font-semibold">
                                {{ $category }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            @if($products->isNotEmpty())
                <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($products as $product)
                        @php
                            $isLow = (float) $product->stock <= (float) $product->low_stock_at;
                        @endphp
                        <article data-reveal data-product-card data-category="{{ $product->category }}"
                                 style="--stagger: {{ $loop->index % 4 }}"
                                 class="group flex flex-col justify-between rounded-[2px] border border-[#192315]/16 bg-[#FAF7EF] p-6 transition-colors duration-200 hover:border-[#192315]/40 hover:bg-white">
                            <div>
                                <div class="flex items-center justify-between gap-3 border-b border-[#192315]/10 pb-3">
                                    <span class="font-mono-num text-xs font-semibold text-[#595548]">
                                        {{ str_pad((string) ($loop->index + 1), 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                    <span class="text-[11px] font-semibold {{ $isLow ? 'text-[#A44A28]' : 'text-[#263822]' }}">
                                        {{ $isLow ? 'Últimas unidades' : 'En stock' }}
                                    </span>
                                </div>

                                <p class="mt-5 text-[10px] font-bold uppercase tracking-[0.16em] text-[#595548]">{{ $product->category }}</p>
                                <h3 class="font-editorial mt-1.5 text-xl font-medium leading-snug text-[#192315]">{{ $product->name }}</h3>
                            </div>

                            <div class="mt-8 flex items-end justify-between border-t border-[#192315]/12 pt-4">
                                <div>
                                    <p class="font-mono-num text-xl font-semibold text-[#192315]">S/ {{ number_format((float) $product->selling_price, 2) }}</p>
                                    <p class="mt-0.5 text-[11px] text-[#595548]">por {{ $product->unit }}</p>
                                </div>
                                <button type="button" data-inquire-product="{{ $product->name }}"
                                    class="inline-flex min-h-[38px] items-center gap-1.5 rounded-[2px] border border-[#192315] bg-transparent px-3.5 py-1.5 text-xs font-semibold text-[#192315] transition-colors hover:bg-[#192315] hover:text-[#F4EFE4]">
                                    <span>Pedir</span>
                                    <x-icon name="arrow-up-right" class="size-3.5" />
                                </button>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="mt-10 flex flex-col items-start justify-between gap-5 border border-[#192315]/16 bg-[#FAF7EF] p-8 sm:flex-row sm:items-center" data-reveal>
                    <div>
                        <h3 class="font-editorial text-2xl font-medium text-[#192315]">Estamos actualizando nuestra despensa</h3>
                        <p class="mt-1.5 text-sm text-[#595548]">Escríbenos directamente y te confirmamos stock y precios del día al instante.</p>
                    </div>
                    <a href="#contacto" class="editorial-btn-primary shrink-0">Consultar disponibilidad</a>
                </div>
            @endif
        </section>

        {{-- 5. PREGUNTAS FRECUENTES (Filetes editoriales en arena cálida) --}}
        @if($sections['faq']->active)
            @php
                $faqLines = collect(preg_split('/\r\n|\r|\n/', (string) $sections['faq']->content))
                    ->map(fn ($line) => trim($line))
                    ->filter();
                $faqItems = $faqLines->map(function ($line) {
                    if (preg_match('/^((?:¿)?[^?]+\?)\s*(.+)$/u', $line, $m)) {
                        return ['q' => trim($m[1]), 'a' => trim($m[2])];
                    }
                    return null;
                })->filter()->values();
            @endphp
            <section id="preguntas" class="border-y border-[#192315]/14 bg-[#EAE3D2]">
                <div class="mx-auto grid max-w-7xl gap-12 px-5 py-20 sm:px-8 lg:grid-cols-[.82fr_1.18fr] lg:py-24">
                    <div data-reveal>
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-[#A44A28]">03 · Orientación</p>
                        <h2 class="font-editorial mt-3 text-4xl font-medium tracking-tight text-[#192315] sm:text-5xl">{{ $sections['faq']->title }}</h2>
                        <p class="mt-4 max-w-sm text-sm leading-7 text-[#4C493E]">Respuestas claras sobre horarios, reservas y coordinación directa en tu barrio.</p>

                        <ul class="mt-8 divide-y divide-[#192315]/12 border-y border-[#192315]/12">
                            @foreach([
                                ['01', 'Pedidos al instante sin cargos adicionales'],
                                ['02', 'Stock actualizado directamente desde tienda'],
                                ['03', 'Atención personalizada por WhatsApp'],
                            ] as [$n, $txt])
                                <li class="flex items-center gap-3.5 py-3 text-xs font-semibold text-[#192315]">
                                    <span class="font-mono-num text-[11px] font-semibold text-[#A44A28]">{{ $n }}</span>
                                    <span>{{ $txt }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    @if($faqItems->isNotEmpty())
                        <div class="divide-y divide-[#192315]/14 border-y border-[#192315]/14" data-reveal style="--stagger: 1">
                            @foreach($faqItems as $item)
                                <div class="faq-item transition-colors" data-open="{{ $loop->first ? 'true' : 'false' }}">
                                    <button type="button" data-faq-trigger aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                        class="flex w-full items-center justify-between gap-4 py-5 text-left text-base font-medium text-[#192315] transition-colors hover:text-[#A44A28]">
                                        <span class="font-editorial text-xl">{{ $item['q'] }}</span>
                                        <span class="faq-chevron grid size-8 shrink-0 place-items-center text-[#192315] transition-transform duration-300">
                                            <x-icon name="chevron-down" class="size-4" />
                                        </span>
                                    </button>
                                    <div class="faq-answer">
                                        <div class="overflow-hidden">
                                            <p class="pb-5 pr-8 text-sm leading-7 text-[#4C493E]">{{ $item['a'] }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="border border-[#192315]/16 bg-[#FAF7EF] p-7" data-reveal>
                            <p class="whitespace-pre-line text-sm leading-8 text-[#4C493E]">{{ $sections['faq']->content }}</p>
                        </div>
                    @endif
                </div>
            </section>
        @endif

        {{-- 6. NOTA DE PEDIDO Y CONTACTO DIRECTO --}}
        @if($sections['contact']->active)
        <section id="contacto" class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:py-24">
            <div class="grid items-start gap-12 lg:grid-cols-[.9fr_1.1fr] lg:gap-16">
                <div data-reveal>
                    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-[#A44A28]">04 · Contacto directo</p>
                    <h2 class="font-editorial mt-3 text-4xl font-medium tracking-tight text-[#192315] sm:text-5xl">{{ $sections['contact']->title }}</h2>
                    <p class="mt-5 max-w-md whitespace-pre-line text-base leading-7 text-[#4C493E]">{{ $sections['contact']->content }}</p>

                    @if($whatsappDigits)
                        <div class="mt-8 flex flex-wrap items-center gap-4 border-t border-[#192315]/14 pt-6">
                            <a href="https://wa.me/{{ $whatsappDigits }}" target="_blank" rel="noopener" class="editorial-btn-primary">
                                <x-icon name="whatsapp" class="size-4" />
                                <span>Escríbenos por WhatsApp</span>
                                <x-icon name="arrow-up-right" class="size-4" />
                            </a>
                            <span class="font-mono-num text-xs font-semibold text-[#595548]">+{{ $whatsappDigits }}</span>
                        </div>
                    @endif
                </div>

                <form action="{{ route('contact.send') }}" method="POST" data-reveal style="--stagger: 1"
                      class="grid gap-5 border border-[#192315]/20 bg-[#FAF7EF] p-6 sm:p-8">
                    @csrf
                    <div class="border-b border-[#192315]/12 pb-4">
                        <h3 class="font-editorial text-2xl font-medium text-[#192315]">Envíanos tu consulta</h3>
                        <p class="mt-1 text-xs text-[#595548]">Te responderemos a la brevedad a tu WhatsApp o correo.</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="text-xs font-semibold text-[#192315]">
                            Tu nombre
                            <input class="editorial-field mt-1.5" name="name" maxlength="160" placeholder="Ej. Ana María" required>
                        </label>
                        <label class="text-xs font-semibold text-[#192315]">
                            WhatsApp
                            <input class="editorial-field font-mono-num mt-1.5" name="phone" type="tel" maxlength="32" placeholder="519XXXXXXXX" required>
                        </label>
                    </div>
                    <label class="text-xs font-semibold text-[#192315]">
                        Correo (opcional)
                        <input class="editorial-field mt-1.5" name="email" type="email" maxlength="255" placeholder="tucorreo@ejemplo.com">
                    </label>
                    <label class="text-xs font-semibold text-[#192315]">
                        ¿En qué podemos ayudarte?
                        <textarea class="editorial-field mt-1.5 min-h-28 resize-y" name="message" maxlength="2000" placeholder="Cuéntanos qué productos buscas o tu consulta…" required></textarea>
                    </label>
                    @if($errors->any())
                        <p role="alert" class="rounded-[2px] border border-[#A44A28]/40 bg-[#A44A28]/10 px-3.5 py-2.5 text-xs font-semibold text-[#7A3217]">{{ $errors->first() }}</p>
                    @endif
                    <button class="editorial-btn-primary mt-1 w-full sm:w-fit">
                        <span>{{ $sections['contact']->cta_label ?: 'Enviar consulta' }}</span>
                        <x-icon name="send" class="size-4" />
                    </button>
                </form>
            </div>
        </section>
        @endif
    </main>
@endsection
