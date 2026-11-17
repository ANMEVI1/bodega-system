<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Tu bodega de confianza: productos frescos, buenos precios y atención cercana.">
    <title>@yield('title', 'Bodega Los Olivos · Cerca de ti')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,SOFT,WONK,wght@0,9..144,0..100,0..1,600..900;1,9..144,0..100,0..1,400..800&family=JetBrains+Mono:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>document.documentElement.classList.add('js-cinema');</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="site-editorial relative min-h-screen antialiased bg-[#FAF7EE] text-[#253017]">
    {{-- Scroll progress hairline --}}
    <div id="scroll-progress" class="fixed inset-x-0 top-0 z-50 h-[3px] origin-left scale-x-0 bg-gradient-to-r from-[#D7F0B4] via-[#D1C3F6] to-[#D19C78]"></div>

    @php
        $contactSection = $sections['contact'];
    @endphp

    {{-- Floating Botanical Header (Inspired by Wix wh-1241) --}}
    <header class="sticky top-0 z-40 px-4 pt-3.5 sm:px-8">
        <nav class="mx-auto flex max-w-7xl items-center justify-between gap-3">
            {{-- Brand Mark --}}
            <a href="{{ route('home') }}" class="group inline-flex items-center gap-3 rounded-full bg-[#253017]/95 px-4 py-2 text-[#FAF8ED] shadow-lg shadow-[#162011]/20 ring-1 ring-white/15 backdrop-blur-md transition duration-300 hover:scale-[1.02]">
                <span class="grid size-8 place-items-center rounded-full bg-[#D1C3F6] text-[#253017] transition duration-300 group-hover:rotate-12">
                    <x-icon name="sprout" class="size-4" />
                </span>
                <span class="pr-1">
                    <span class="font-serif-display block text-base font-extrabold leading-none tracking-tight">Bodega Los Olivos</span>
                    <span class="mt-0.5 block text-[10px] font-semibold tracking-wide text-[#D7F0B4]/90">Comercio & Barrio</span>
                </span>
            </a>

            {{-- Floating Cream Pill Nav --}}
            <div class="pill-nav flex items-center gap-1 rounded-full p-1.5">
                <a href="#nosotros" class="hidden rounded-full px-4 py-2 text-xs font-bold text-[#31190F]/80 transition hover:bg-[#F3EAD7] hover:text-[#253017] md:inline-block">Sobre</a>
                <a href="#productos" class="hidden rounded-full px-4 py-2 text-xs font-bold text-[#31190F]/80 transition hover:bg-[#F3EAD7] hover:text-[#253017] md:inline-block">Catálogo</a>
                <a href="#preguntas" class="hidden rounded-full px-4 py-2 text-xs font-bold text-[#31190F]/80 transition hover:bg-[#F3EAD7] hover:text-[#253017] lg:inline-block">Preguntas</a>
                <a href="#contacto" class="hidden rounded-full px-4 py-2 text-xs font-bold text-[#31190F]/80 transition hover:bg-[#F3EAD7] hover:text-[#253017] sm:inline-block">Contacto</a>

                <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 rounded-full border border-[#253017]/15 bg-[#EFEAFE] px-3.5 py-2 text-xs font-extrabold text-[#253017] transition hover:bg-[#E1D8FB]">
                    <x-icon name="shield" class="size-3.5 text-[#5b4696]" />
                    <span>Panel Admin</span>
                </a>

                <a href="{{ $contactSection->whatsapp ? 'https://wa.me/'.preg_replace('/\D+/', '', $contactSection->whatsapp) : '#contacto' }}"
                   @if($contactSection->whatsapp) target="_blank" rel="noopener" @endif
                   class="inline-flex items-center gap-1.5 rounded-full bg-[#D7F0B4] px-4 py-2 text-xs font-extrabold text-[#1E2B14] transition duration-300 hover:bg-[#c6ea96]">
                    <x-icon name="whatsapp" class="size-3.5" />
                    <span class="hidden sm:inline">WhatsApp</span>
                    <x-icon name="arrow-up-right" class="size-3.5" />
                </a>
            </div>
        </nav>
    </header>

    @if(session('contact_sent'))
        <div role="status" data-toast class="fixed right-5 top-24 z-50 max-w-md overflow-hidden rounded-3xl border border-[#253017]/15 bg-[#FFFFF3] p-4 text-sm text-[#253017] shadow-2xl transition duration-300">
            <div class="flex items-start gap-3">
                <span class="grid size-8 shrink-0 place-items-center rounded-full bg-[#D7F0B4] text-[#253017]">
                    <x-icon name="check-circle" class="size-4" />
                </span>
                <p class="flex-1 text-xs font-semibold leading-5">{{ session('contact_sent') }}</p>
                <button type="button" data-toast-close class="text-[#5A5344] hover:text-[#253017]" aria-label="Cerrar">
                    <x-icon name="x" class="size-4" />
                </button>
            </div>
            <div class="toast-progress mt-3 h-1 w-full rounded-full bg-[#D7F0B4]"></div>
        </div>
    @endif

    @yield('content')

    <footer class="border-t border-white/10 bg-[#162012] px-5 py-14 text-[#E6E3D3] sm:px-8">
        <div class="mx-auto grid max-w-7xl gap-10 md:grid-cols-[1.2fr_.9fr_.9fr]">
            <div>
                <div class="inline-flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-2xl bg-[#D1C3F6] text-[#253017]">
                        <x-icon name="sprout" class="size-5" />
                    </span>
                    <div>
                        <p class="font-serif-display text-xl font-extrabold text-[#FAF8ED]">Bodega Los Olivos</p>
                        <p class="text-[11px] text-[#D7F0B4]">Gestión comercial & atención de barrio</p>
                    </div>
                </div>
                <p class="mt-4 max-w-sm text-xs leading-6 text-[#C7C9BA]">
                    Abastecimiento diario, productos seleccionados y trato directo para tu hogar y negocio.
                </p>
            </div>

            <div class="text-xs">
                <p class="font-extrabold uppercase tracking-[.18em] text-[#D7F0B4]">Navegación</p>
                <ul class="mt-4 space-y-2.5 text-[#D8DACB]">
                    <li><a href="#nosotros" class="transition hover:text-white">Sobre nosotros</a></li>
                    <li><a href="#productos" class="transition hover:text-white">Catálogo disponible</a></li>
                    <li><a href="#preguntas" class="transition hover:text-white">Preguntas frecuentes</a></li>
                    <li><a href="#contacto" class="transition hover:text-white">Contacto</a></li>
                </ul>
            </div>

            <div class="text-xs">
                <p class="font-extrabold uppercase tracking-[.18em] text-[#D1C3F6]">Gestión interna</p>
                <p class="mt-4 leading-6 text-[#C7C9BA]">Control de inventario, ventas, clientes y editor web en tiempo real.</p>
                <div class="mt-4 flex flex-wrap gap-2.5">
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-full bg-[#D7F0B4] px-4 py-2 text-xs font-extrabold text-[#1E2B14] transition hover:brightness-105">
                        <x-icon name="shield" class="size-3.5" />
                        Acceso administrador
                    </a>
                </div>
            </div>
        </div>

        <div class="mx-auto mt-10 flex max-w-7xl flex-col items-center justify-between gap-3 border-t border-white/10 pt-6 text-[11px] text-[#9FA493] sm:flex-row">
            <p>© {{ date('Y') }} Bodega Los Olivos · Hecho con cariño para tu barrio</p>
            <p>Operado con <span class="font-bold text-[#D7F0B4]">NEXO Bodega</span></p>
        </div>
    </footer>
</body>
</html>
