@extends('layouts.site')
@section('content')
    @php
        $hero = $sections['home']->active ? $sections['home'] : $sections['banner'];
        $promoSection = $sections['promo'] ?? null;
        $hasPromoStage = ($promoSection?->active ?? true) && isset($promoMedia) && $promoMedia->isNotEmpty();
        $featuredProduct = $products->first();
        $whatsappDigits = $sections['contact']->whatsapp ? preg_replace('/\D+/', '', $sections['contact']->whatsapp) : null;
    @endphp
    <main class="{{ ($hasPromoStage || $sections['home']->active || $sections['banner']->active) ? '-mt-[74px]' : 'pt-6' }}">
        {{-- 1. FULL-BLEED BOTANICAL HERO (Wix wh-1241 Composition + Upper Advertising/Presentation Stage) --}}
        @if($hasPromoStage || $sections['home']->active || $sections['banner']->active)
        <section class="hero-botanical relative isolate overflow-hidden pb-20 pt-28 text-[#FAF8ED] sm:pb-28 sm:pt-32 lg:pb-32 lg:pt-36">
            {{-- Atmospheric soft bokeh & grain --}}
            <div class="cinema-grain absolute inset-0 z-10 opacity-40"></div>
            <div class="ambient-emerald pointer-events-none absolute -right-24 top-10 size-[34rem] rounded-full blur-[120px]"></div>
            <div class="ambient pointer-events-none absolute -left-32 bottom-0 size-[30rem] rounded-full blur-[120px] opacity-60"></div>

            {{-- 1A. UPPER GREEN AREA: PUBLICIDAD Y PRESENTACIÓN MULTIMEDIA (IMAGEN / VIDEO / CARRUSEL) --}}
            @if($hasPromoStage)
                <div data-cinema-enter style="--enter-delay: 0" class="relative z-20 mx-auto mb-12 max-w-7xl px-5 sm:mb-16 sm:px-8">
                    <div data-promo-carousel class="group/promo relative overflow-hidden rounded-[2.2rem] border border-[#FAF8ED]/18 bg-[#121C0E]/90 shadow-[0_28px_70px_-20px_rgba(0,0,0,0.65)] ring-1 ring-[#D7F0B4]/15">
                        <div class="relative">
                            @foreach($promoMedia as $slide)
                                @php
                                    $mediaSrc = $slide->mediaUrl();
                                    $embedSrc = $slide->embedUrl();
                                    $isVideo = $slide->media_type === 'video';
                                    $fitClass = match ($slide->fit_mode) {
                                        'contain' => 'max-h-[520px] min-h-[260px] sm:min-h-[380px] object-contain bg-[#0E170B]',
                                        'auto' => 'h-auto max-h-[620px] object-contain bg-[#0E170B]',
                                        default => 'h-[280px] sm:h-[400px] lg:h-[470px] object-cover',
                                    };
                                    $hasOverlayContent = $slide->show_overlay && ($slide->badge || $slide->title || $slide->subtitle || $slide->cta_label);
                                @endphp
                                <div data-promo-slide="{{ $loop->index }}"
                                     class="{{ $loop->first ? 'block' : 'hidden' }} relative w-full transition-opacity duration-500">
                                    @if($isVideo && $embedSrc)
                                        <div class="relative w-full h-[280px] sm:h-[400px] lg:h-[470px] bg-black">
                                            <iframe src="{{ $embedSrc }}"
                                                    title="{{ $slide->title ?: 'Presentación en video' }}"
                                                    class="h-full w-full border-0"
                                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                                    allowfullscreen></iframe>
                                        </div>
                                    @elseif($isVideo && $mediaSrc)
                                        <video controls playsinline preload="metadata"
                                               class="block w-full {{ $fitClass }}">
                                            <source src="{{ $mediaSrc }}" type="{{ $slide->mime_type ?: 'video/mp4' }}">
                                            Tu navegador no soporta la reproducción de video.
                                        </video>
                                    @elseif($mediaSrc)
                                        <img src="{{ $mediaSrc }}"
                                             alt="{{ $slide->title ?: ($promoSection->title ?? 'Publicidad Bodega Los Olivos') }}"
                                             class="block w-full {{ $fitClass }}">
                                    @endif

                                    @if($hasOverlayContent)
                                        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-[#0D150A]/90 via-[#0D150A]/35 to-transparent"></div>
                                        <div class="pointer-events-none inset-x-0 bottom-0 z-10 flex flex-col justify-end p-6 sm:p-10 lg:p-12 {{ $isVideo ? 'relative bg-[#121C0E]/95 sm:absolute sm:bg-transparent' : 'absolute inset-0' }}">
                                            <div class="max-w-2xl">
                                                @if($slide->badge)
                                                    <span class="inline-flex items-center gap-2 rounded-full border border-[#D7F0B4]/35 bg-[#192613]/85 px-3.5 py-1 text-[10px] font-extrabold uppercase tracking-[.18em] text-[#D7F0B4] backdrop-blur-md">
                                                        <span class="status-ping size-2 rounded-full bg-[#D7F0B4]"></span>
                                                        {{ $slide->badge }}
                                                    </span>
                                                @endif
                                                @if($slide->title)
                                                    <h2 class="font-serif-display mt-3 text-2xl font-black leading-tight text-[#FAF8ED] drop-shadow-md sm:text-4xl lg:text-5xl">
                                                        {{ $slide->title }}
                                                    </h2>
                                                @endif
                                                @if($slide->subtitle)
                                                    <p class="mt-2.5 max-w-xl whitespace-pre-line text-xs leading-relaxed text-[#E5E7D7] drop-shadow sm:text-sm lg:text-base">
                                                        {{ $slide->subtitle }}
                                                    </p>
                                                @endif
                                                @if($slide->cta_label)
                                                    <div class="pointer-events-auto mt-5 flex flex-wrap items-center gap-3">
                                                        <a href="{{ $slide->cta_url ?: '#productos' }}" class="btn-matcha text-xs sm:text-sm">
                                                            <span>{{ $slide->cta_label }}</span>
                                                            <x-icon name="arrow-right" class="size-4" />
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if($promoMedia->count() > 1)
                            {{-- Carousel Navigation Controls --}}
                            <button type="button" data-promo-prev aria-label="Anterior presentación"
                                    class="absolute left-3.5 top-1/2 z-20 -translate-y-1/2 grid size-10 place-items-center rounded-full border border-white/20 bg-[#162012]/80 text-[#FAF8ED] shadow-lg backdrop-blur-md transition hover:scale-105 hover:bg-[#253017] sm:left-5 sm:size-11">
                                <x-icon name="chevron-left" class="size-5" />
                            </button>
                            <button type="button" data-promo-next aria-label="Siguiente presentación"
                                    class="absolute right-3.5 top-1/2 z-20 -translate-y-1/2 grid size-10 place-items-center rounded-full border border-white/20 bg-[#162012]/80 text-[#FAF8ED] shadow-lg backdrop-blur-md transition hover:scale-105 hover:bg-[#253017] sm:right-5 sm:size-11">
                                <x-icon name="chevron-right" class="size-5" />
                            </button>

                            <div class="absolute bottom-4 right-5 z-20 flex items-center gap-2 rounded-full border border-white/15 bg-[#121C0E]/80 px-3 py-1.5 backdrop-blur-md sm:bottom-6 sm:right-8">
                                @foreach($promoMedia as $dotSlide)
                                    <button type="button" data-promo-dot="{{ $loop->index }}"
                                            aria-label="Ir a diapositiva {{ $loop->iteration }}"
                                            class="h-2 rounded-full transition-all duration-300 {{ $loop->first ? 'w-6 bg-[#D7F0B4]' : 'w-2 bg-white/40 hover:bg-white/70' }}"></button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if($sections['home']->active || $sections['banner']->active)
            <div class="relative z-20 mx-auto grid max-w-7xl items-center gap-12 px-5 sm:px-8 lg:grid-cols-[1.12fr_.88fr]">
                <div>
                    <div data-cinema-enter style="--enter-delay: 0">
                        <span class="inline-flex items-center gap-2.5 rounded-full border border-[#D7F0B4]/25 bg-[#FAF8ED]/10 px-4 py-1.5 text-[11px] font-bold uppercase tracking-[.16em] text-[#D7F0B4] backdrop-blur-md">
                            <span class="status-ping size-2 rounded-full bg-[#D7F0B4]"></span>
                            Bodega Los Olivos · Atención diaria
                        </span>
                    </div>

                    <div class="relative mt-6 flex flex-wrap items-center gap-4">
                        <h1 data-cinema-enter style="--enter-delay: 1"
                            class="font-serif-display max-w-2xl text-5xl font-black leading-[0.98] tracking-tight text-[#FAF8ED] sm:text-7xl">
                            {{ $hero->title }}
                        </h1>

                        {{-- Hand-drawn Botanical Lavender Vector Emblem (wh-1241 signature detail) --}}
                        <div data-cinema-enter style="--enter-delay: 2" class="float-emblem hidden sm:block">
                            <svg class="size-20 drop-shadow-lg" viewBox="0 0 96 96" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M52 14C66 20 79 34 77 52C75 68 62 79 44 80C29 81 18 70 19 54C20 40 31 32 41 34C38 25 43 17 52 14Z" fill="#D1C3F6" stroke="#253017" stroke-width="2.2"/>
                                <path d="M35 61C46 54 57 45 64 33" stroke="#253017" stroke-width="2.2" stroke-linecap="round"/>
                                <path d="M47 49C53 49 58 51 62 54" stroke="#253017" stroke-width="2" stroke-linecap="round"/>
                                <path d="M42 56C44 62 44 67 42 71" stroke="#253017" stroke-width="2" stroke-linecap="round"/>
                                <circle cx="59" cy="29" r="3" fill="#D7F0B4" stroke="#253017" stroke-width="1.5"/>
                            </svg>
                        </div>
                    </div>

                    <p data-cinema-enter style="--enter-delay: 2"
                       class="mt-6 max-w-xl whitespace-pre-line text-base leading-7 text-[#E5E7D7] sm:text-lg">
                        {{ $hero->content }}
                    </p>

                    <div data-cinema-enter style="--enter-delay: 3" class="mt-9 flex flex-wrap items-center gap-3.5">
                        <a href="{{ $hero->cta_url ?: '#productos' }}" class="btn-matcha text-sm">
                            <span>{{ $hero->cta_label ?: 'Explorar catálogo' }}</span>
                            <x-icon name="arrow-right" class="size-4" />
                        </a>
                        <a href="#contacto" class="inline-flex items-center gap-2 rounded-full border border-[#FAF8ED]/25 bg-[#FAF8ED]/10 px-6 py-3.5 text-sm font-bold text-[#FAF8ED] backdrop-blur-md transition duration-300 hover:bg-[#FAF8ED]/20">
                            <x-icon name="message" class="size-4 text-[#D1C3F6]" />
                            Escríbenos
                        </a>
                    </div>
                </div>

                {{-- 3D Interactive Showcase Stage --}}
                <div data-cinema-enter style="--enter-delay: 2" class="relative mx-auto w-full max-w-lg">
                    <div class="pointer-events-none absolute -inset-6 rounded-[3rem] bg-[#D7F0B4]/15 blur-3xl"></div>
                    <div class="tilt-stage relative overflow-hidden rounded-[2.2rem] border border-[#FAF8ED]/15 bg-[#FAF7EE] p-6 text-[#253017] shadow-2xl sm:p-8">
                        <div class="tilt-glare rounded-[2.2rem]"></div>

                        <div class="relative z-20 flex items-start justify-between gap-4">
                            <div>
                                <span class="inline-block rounded-full bg-[#EFEAFE] px-3 py-1 text-[10px] font-extrabold uppercase tracking-[.18em] text-[#4C3882]">
                                    Vitrina de hoy
                                </span>
                                <h2 class="font-serif-display mt-3 text-2xl font-extrabold leading-tight text-[#253017] sm:text-3xl">
                                    {{ $sections['banner']->active ? $sections['banner']->title : 'Fresco. Cerca. Para ti.' }}
                                </h2>
                                @if($sections['banner']->active)
                                    <p class="mt-2 whitespace-pre-line text-xs leading-6 text-[#5A5344]">{{ $sections['banner']->content }}</p>
                                @endif
                            </div>
                            <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-[#D7F0B4] text-[#253017]">
                                <x-icon name="sprout" class="size-5" />
                            </span>
                        </div>

                        <div class="relative z-20 my-6 grid grid-cols-2 gap-3">
                            @foreach([
                                ['package', 'Abarrotes', 'Stock renovado', 'bg-[#EDF7DD] text-[#253017]'],
                                ['clock', 'Entrega ágil', 'Listo al instante', 'bg-[#EFEAFE] text-[#3B2B6E]'],
                                ['shopping-bag', 'Selección fresca', 'Calidad diaria', 'bg-[#F7ECE0] text-[#5B351A]'],
                                ['whatsapp', 'Atención directa', 'Sin esperas', 'bg-[#E3F5EE] text-[#174A34]'],
                            ] as [$icon, $title, $sub, $chip])
                                <div class="rounded-2xl border border-[#253017]/10 bg-white/85 p-3.5 transition duration-300 hover:-translate-y-0.5 hover:shadow-md">
                                    <span class="grid size-8 place-items-center rounded-xl {{ $chip }}">
                                        <x-icon :name="$icon" class="size-4" />
                                    </span>
                                    <p class="mt-2.5 text-xs font-extrabold text-[#253017]">{{ $title }}</p>
                                    <p class="text-[11px] text-[#6A6353]">{{ $sub }}</p>
                                </div>
                            @endforeach
                        </div>

                        @if($featuredProduct)
                            <div class="relative z-20 flex items-center justify-between gap-3 rounded-2xl bg-[#253017] p-4 text-[#FAF8ED]">
                                <div class="min-w-0">
                                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-[#D7F0B4]">Producto destacado</p>
                                    <p class="mt-0.5 truncate text-sm font-bold">{{ $featuredProduct->name }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="font-mono-num text-base font-extrabold text-[#D7F0B4]">S/ {{ number_format((float) $featuredProduct->selling_price, 2) }}</p>
                                    <p class="text-[10px] text-[#D8DACB]">{{ number_format((float) $featuredProduct->stock, 0) }} {{ $featuredProduct->unit }} disp.</p>
                                </div>
                            </div>
                        @else
                            <div class="relative z-20 rounded-2xl bg-[#253017] p-4 text-[#FAF8ED]">
                                <p class="text-[10px] font-extrabold uppercase tracking-wider text-[#D7F0B4]">Tu bodega de confianza</p>
                                <p class="mt-1 text-xs text-[#D8DACB]">Consúltanos disponibilidad, pedidos especiales y ofertas del día.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </section>
        @endif

        {{-- 2. SOBRE NOSOTROS & 3 PILLARS (Warm Ivory Canvas · wh-1241 "Un lugar cálido para crecer") --}}
        @if($sections['about']->active)
        <section id="nosotros" class="mx-auto max-w-7xl px-5 py-20 sm:px-8 sm:py-24">
            <div class="mx-auto max-w-3xl text-center" data-reveal>
                <span class="inline-flex items-center gap-2 rounded-full bg-[#EFEAFE] px-4 py-1.5 text-[11px] font-extrabold uppercase tracking-[.18em] text-[#4B3881]">
                    Sobre nosotros
                </span>
                <h2 class="font-serif-display mt-5 text-4xl font-black tracking-tight text-[#253017] sm:text-5xl">
                    {{ $sections['about']->title }}
                </h2>
                <p class="mt-5 whitespace-pre-line text-base leading-8 text-[#5A5344] sm:text-lg">
                    {{ $sections['about']->content }}
                </p>
            </div>

            <div class="mt-14 grid gap-5 md:grid-cols-3">
                @foreach([
                    ['01', 'Abastecimiento cuidado', 'Trabajamos con proveedores seleccionados para mantener frescura y rotación continua en cada categoría.', 'bg-[#EDF7DD] border-[#BBD992]', 'bg-[#253017] text-[#D7F0B4]', 'sprout'],
                    ['02', 'Precios transparentes', 'Control preciso de costos y márgenes justos para ofrecerte el mejor valor del barrio todos los días.', 'bg-[#EFEAFE] border-[#C9B9F5]', 'bg-[#3C2B70] text-[#EFEAFE]', 'wallet'],
                    ['03', 'Atención cercana y ágil', 'Consulta nuestro catálogo en tiempo real y separa tu pedido por WhatsApp sin hacer colas.', 'bg-[#F7ECE0] border-[#DFC0A3]', 'bg-[#5A361C] text-[#FAF8ED]', 'whatsapp'],
                ] as [$num, $title, $desc, $cardTone, $badgeTone, $icon])
                    <article data-reveal style="--stagger: {{ $loop->index }}"
                             class="spotlight-card spotlight-sage rounded-[2rem] border p-7 transition duration-300 hover:-translate-y-1.5 {{ $cardTone }}">
                        <div class="flex items-center justify-between">
                            <span class="grid size-12 place-items-center rounded-2xl {{ $badgeTone }}">
                                <x-icon :name="$icon" class="size-5" />
                            </span>
                            <span class="font-mono-num text-2xl font-extrabold text-[#253017]/25">{{ $num }}</span>
                        </div>
                        <h3 class="font-serif-display mt-6 text-2xl font-extrabold text-[#253017]">{{ $title }}</h3>
                        <p class="mt-3 text-sm leading-6 text-[#4D473A]">{{ $desc }}</p>
                    </article>
                @endforeach
            </div>
        </section>
        @endif

        {{-- 3. EDITORIAL RHYTHM STATEMENT & MARQUEE (wh-1241 "El ritmo de nuestro día") --}}
        <section class="border-y border-[#253017]/10 bg-[#F3EAD7]">
            <div class="mx-auto max-w-5xl px-5 py-16 text-center sm:px-8 sm:py-20" data-reveal>
                <p class="text-[11px] font-extrabold uppercase tracking-[.22em] text-[#794C2A]">El ritmo de nuestra bodega</p>
                <p class="font-serif-display mt-5 text-2xl font-bold leading-snug text-[#253017] sm:text-4xl">
                    Desde el primer café de la mañana hasta la compra de último minuto, combinamos
                    <span class="italic text-[#5B4696]">selección fresca</span>,
                    <span class="italic text-[#794C2A]">precios claros</span> y una
                    <span class="underline decoration-[#98C85A] decoration-4 underline-offset-4">atención siempre cercana</span>.
                </p>
            </div>

            <div class="overflow-hidden border-t border-[#253017]/10 bg-[#FAF7EE] py-3.5">
                <div class="marquee-track flex items-center gap-10 text-xs font-bold tracking-wide text-[#4D473A]">
                    @for($loopCopy = 0; $loopCopy < 2; $loopCopy++)
                        <span class="flex items-center gap-2.5"><span class="size-2 rounded-full bg-[#98C85A]"></span>Abarrotes y productos esenciales</span>
                        <span class="flex items-center gap-2.5"><span class="size-2 rounded-full bg-[#B59DF0]"></span>Stock actualizado en tiempo real</span>
                        <span class="flex items-center gap-2.5"><span class="size-2 rounded-full bg-[#D19C78]"></span>Pedidos rápidos por WhatsApp</span>
                        <span class="flex items-center gap-2.5"><span class="size-2 rounded-full bg-[#253017]"></span>Precios justos para tu hogar</span>
                        <span class="flex items-center gap-2.5"><span class="size-2 rounded-full bg-[#98C85A]"></span>Calidad garantizada en tu barrio</span>
                    @endfor
                </div>
            </div>
        </section>

        {{-- 4. CATÁLOGO INTERACTIVO (Numbered Cards 01 / 02 / 03...) --}}
        <section id="productos" class="mx-auto max-w-7xl px-5 py-20 sm:px-8 sm:py-24">
            <div class="flex flex-wrap items-end justify-between gap-6" data-reveal>
                <div>
                    <span class="inline-block rounded-full bg-[#D7F0B4] px-3.5 py-1 text-[11px] font-extrabold uppercase tracking-[.16em] text-[#202d15]">
                        Catálogo disponible
                    </span>
                    <h2 class="font-serif-display mt-3 text-3xl font-black tracking-tight text-[#253017] sm:text-5xl">
                        Un vistazo a nuestros productos
                    </h2>
                </div>

                @if(isset($categories) && $categories->count() > 1)
                    <div class="flex flex-wrap gap-2" role="group" aria-label="Filtrar por categoría">
                        <button type="button" data-category-filter="all" aria-pressed="true"
                            class="rounded-full border border-[#253017]/25 bg-[#D7F0B4] px-4 py-2 text-xs font-extrabold text-[#202d15] shadow-sm transition">
                            Todos
                        </button>
                        @foreach($categories as $category)
                            <button type="button" data-category-filter="{{ $category }}" aria-pressed="false"
                                class="rounded-full border border-[#253017]/15 bg-white px-4 py-2 text-xs font-bold text-[#4A463B] transition hover:border-[#253017]/30 hover:text-[#253017]">
                                {{ $category }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            @if($products->isNotEmpty())
                <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($products as $product)
                        @php
                            $isLow = (float) $product->stock <= (float) $product->low_stock_at;
                        @endphp
                        <article data-reveal data-product-card data-category="{{ $product->category }}"
                                 style="--stagger: {{ $loop->index % 4 }}"
                                 class="editorial-card spotlight-card spotlight-sage group flex flex-col rounded-[2rem] p-5 transition duration-300 hover:-translate-y-1.5">
                            <div class="flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <span class="font-mono-num inline-flex items-center rounded-full bg-[#F3EAD7] px-2.5 py-1 text-[11px] font-bold text-[#794C2A]">
                                        {{ str_pad((string) ($loop->index + 1), 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                    <span class="rounded-full px-2.5 py-1 text-[10px] font-extrabold {{ $isLow ? 'bg-[#FCE8D5] text-[#8E4713]' : 'bg-[#EDF7DD] text-[#253017]' }}">
                                        {{ $isLow ? 'Últimas unidades' : 'En stock' }}
                                    </span>
                                </div>

                                <p class="mt-5 text-[10px] font-extrabold uppercase tracking-[.16em] text-[#7A7262]">{{ $product->category }}</p>
                                <h3 class="mt-1.5 text-base font-extrabold leading-snug text-[#253017]">{{ $product->name }}</h3>
                            </div>

                            <div class="mt-5 flex items-end justify-between border-t border-[#253017]/10 pt-4">
                                <div>
                                    <p class="font-mono-num text-xl font-extrabold text-[#253017]">S/ {{ number_format((float) $product->selling_price, 2) }}</p>
                                    <p class="mt-0.5 text-[10px] font-medium text-[#7A7262]">por {{ $product->unit }}</p>
                                </div>
                                <button type="button" data-inquire-product="{{ $product->name }}"
                                    class="inline-flex items-center gap-1 rounded-full bg-[#253017] px-3.5 py-2 text-[11px] font-bold text-[#FAF8ED] transition hover:bg-[#364722]">
                                    Pedir
                                    <x-icon name="arrow-up-right" class="size-3.5 text-[#D7F0B4]" />
                                </button>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="editorial-card mt-10 flex flex-col items-start justify-between gap-4 rounded-[2rem] p-8 sm:flex-row sm:items-center" data-reveal>
                    <div>
                        <h3 class="font-serif-display text-2xl font-extrabold text-[#253017]">Estamos organizando nuestro catálogo</h3>
                        <p class="mt-1 text-sm text-[#5A5344]">Escríbenos directamente y te confirmamos stock y precios del día al instante.</p>
                    </div>
                    <a href="#contacto" class="btn-matcha shrink-0 text-xs">Consultar disponibilidad</a>
                </div>
            @endif
        </section>

        {{-- 5. PREGUNTAS FRECUENTES & CONFIANZA NUMERADA (01 / 02 / 03) --}}
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
            <section id="preguntas" class="border-t border-[#253017]/10 bg-[#F3EAD7]/70">
                <div class="mx-auto grid max-w-7xl gap-12 px-5 py-20 sm:px-8 sm:py-24 lg:grid-cols-[.85fr_1.15fr]">
                    <div data-reveal>
                        <span class="inline-block rounded-full bg-[#EFEAFE] px-3.5 py-1 text-[11px] font-extrabold uppercase tracking-[.16em] text-[#4B3881]">
                            Te ayudamos
                        </span>
                        <h2 class="font-serif-display mt-3 text-3xl font-black tracking-tight text-[#253017] sm:text-5xl">{{ $sections['faq']->title }}</h2>
                        <p class="mt-4 max-w-sm text-sm leading-7 text-[#5A5344]">Resolvemos las dudas más comunes sobre horarios, reservas y entregas en tu barrio.</p>

                        <div class="mt-8 space-y-3">
                            @foreach([
                                ['01', 'Pedidos al instante sin cargos adicionales'],
                                ['02', 'Stock actualizado directamente desde tienda'],
                                ['03', 'Atención personalizada por WhatsApp'],
                            ] as [$n, $txt])
                                <div class="flex items-center gap-3 rounded-2xl bg-[#FFFFF8] px-4 py-3 text-xs font-bold text-[#253017] ring-1 ring-[#253017]/10">
                                    <span class="font-mono-num rounded-lg bg-[#D7F0B4] px-2 py-0.5 text-[11px] font-extrabold">{{ $n }}</span>
                                    <span>{{ $txt }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if($faqItems->isNotEmpty())
                        <div class="space-y-3" data-reveal style="--stagger: 1">
                            @foreach($faqItems as $item)
                                <div class="faq-item editorial-card rounded-3xl transition" data-open="{{ $loop->first ? 'true' : 'false' }}">
                                    <button type="button" data-faq-trigger aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                        class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left text-sm font-extrabold text-[#253017]">
                                        <span>{{ $item['q'] }}</span>
                                        <span class="faq-chevron grid size-8 shrink-0 place-items-center rounded-full bg-[#F3EAD7] text-[#253017] transition-transform duration-300">
                                            <x-icon name="chevron-down" class="size-4" />
                                        </span>
                                    </button>
                                    <div class="faq-answer">
                                        <div class="overflow-hidden">
                                            <p class="border-t border-[#253017]/10 px-6 pb-5 pt-4 text-sm leading-7 text-[#5A5344]">{{ $item['a'] }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="editorial-card rounded-3xl p-7" data-reveal>
                            <p class="whitespace-pre-line text-sm leading-8 text-[#4A463B]">{{ $sections['faq']->content }}</p>
                        </div>
                    @endif
                </div>
            </section>
        @endif

        {{-- 6. CLOSING BOTANICAL CTA & CONTACTO --}}
        @if($sections['contact']->active)
        <section id="contacto" class="hero-botanical relative overflow-hidden text-[#FAF8ED]">
            <div class="cinema-grain absolute inset-0 z-10 opacity-35"></div>
            <div class="relative z-20 mx-auto grid max-w-7xl items-center gap-12 px-5 py-20 sm:px-8 sm:py-24 lg:grid-cols-2">
                <div data-reveal>
                    <span class="inline-block rounded-full bg-[#D1C3F6]/20 px-3.5 py-1 text-[11px] font-extrabold uppercase tracking-[.18em] text-[#D1C3F6]">
                        ¿Listo para tu pedido?
                    </span>
                    <h2 class="font-serif-display mt-4 text-4xl font-black tracking-tight text-[#FAF8ED] sm:text-5xl">{{ $sections['contact']->title }}</h2>
                    <p class="mt-5 max-w-md whitespace-pre-line text-base leading-7 text-[#E2E4D4]">{{ $sections['contact']->content }}</p>

                    @if($whatsappDigits)
                        <div class="mt-8 flex flex-wrap items-center gap-4">
                            <a href="https://wa.me/{{ $whatsappDigits }}" target="_blank" rel="noopener" class="btn-matcha text-sm">
                                <x-icon name="whatsapp" class="size-4" />
                                <span>Escríbenos por WhatsApp</span>
                                <x-icon name="arrow-up-right" class="size-4" />
                            </a>
                            <span class="font-mono-num text-xs text-[#D7F0B4]">+{{ $whatsappDigits }}</span>
                        </div>
                    @endif
                </div>

                <form action="{{ route('contact.send') }}" method="POST" data-reveal style="--stagger: 1"
                      class="grid gap-4 rounded-[2.2rem] bg-[#FAF7EE] p-6 text-[#253017] shadow-2xl sm:p-8">
                    @csrf
                    <div class="mb-1">
                        <h3 class="font-serif-display text-2xl font-extrabold text-[#253017]">Envíanos tu consulta</h3>
                        <p class="mt-1 text-xs text-[#5A5344]">Te responderemos a la brevedad a tu WhatsApp o correo.</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="text-xs font-extrabold text-[#253017]">
                            Tu nombre
                            <input class="field-editorial mt-2" name="name" maxlength="160" placeholder="Ej. Ana María" required>
                        </label>
                        <label class="text-xs font-extrabold text-[#253017]">
                            WhatsApp
                            <input class="field-editorial mt-2 font-mono-num" name="phone" type="tel" maxlength="32" placeholder="519XXXXXXXX" required>
                        </label>
                    </div>
                    <label class="text-xs font-extrabold text-[#253017]">
                        Correo (opcional)
                        <input class="field-editorial mt-2" name="email" type="email" maxlength="255" placeholder="tucorreo@ejemplo.com">
                    </label>
                    <label class="text-xs font-extrabold text-[#253017]">
                        ¿En qué podemos ayudarte?
                        <textarea class="field-editorial mt-2 min-h-28 resize-y" name="message" maxlength="2000" placeholder="Cuéntanos qué productos buscas o tu consulta…" required></textarea>
                    </label>
                    @if($errors->any())
                        <p role="alert" class="rounded-xl border border-rose-500/25 bg-rose-500/10 px-3.5 py-2.5 text-xs font-semibold text-rose-800">{{ $errors->first() }}</p>
                    @endif
                    <button class="btn-matcha mt-1 w-full sm:w-fit">
                        <span>{{ $sections['contact']->cta_label ?: 'Enviar consulta' }}</span>
                        <x-icon name="send" class="size-4" />
                    </button>
                </form>
            </div>
        </section>
        @endif
    </main>
@endsection
