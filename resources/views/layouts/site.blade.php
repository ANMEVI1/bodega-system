<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Tu bodega de confianza: productos frescos, buenos precios y atención cercana.">
    <title>@yield('title', 'Bodega Los Olivos · Cerca de ti')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;600&family=Newsreader:ital,opsz,wght@0,6..72,400..700;1,6..72,400..600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>document.documentElement.classList.add('js-cinema');</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="site-editorial relative min-h-screen antialiased">
    {{-- Fondo editorial cálido con retícula sutil --}}
    <div class="site-backdrop-layer pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
        <div class="site-grid-editorial absolute inset-0"></div>
    </div>

    {{-- Indicador superior de lectura --}}
    <div id="scroll-progress" class="fixed inset-x-0 top-0 z-50 h-[2px] origin-left scale-x-0 bg-gradient-to-r from-[#263822] via-[#A44A28] to-[#192315]"></div>

    @php
        $contactSection = $sections['contact'];
        $headerWhatsapp = $contactSection->whatsapp ? preg_replace('/\D+/', '', $contactSection->whatsapp) : null;
    @endphp

    {{-- Cabecera editorial --}}
    <header class="sticky top-0 z-40 border-b border-[#192315]/12 bg-[#F4EFE4]/92 backdrop-blur-md">
        <nav class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-3.5 sm:px-8" aria-label="Navegación principal">
            <a href="{{ route('home') }}" class="group inline-flex items-center gap-3 text-[#192315] transition-opacity hover:opacity-85">
                <span class="grid size-9 place-items-center rounded-[2px] bg-[#192315] text-[#F4EFE4]">
                    <x-icon name="sprout" class="size-4 text-[#FAF7EF] transition-transform duration-300 group-hover:scale-110" />
                </span>
                <span>
                    <span class="font-editorial block text-lg font-medium leading-none tracking-tight sm:text-xl">Bodega Los Olivos</span>
                    <span class="mt-1 block text-[10px] font-semibold uppercase tracking-[0.16em] text-[#595548]">Despensa de barrio · Perú</span>
                </span>
            </a>

            <div class="hidden items-center gap-7 text-xs font-semibold tracking-wide text-[#595548] md:flex">
                <a href="#nosotros" class="py-1 transition-colors hover:text-[#192315]">Sobre nosotros</a>
                <a href="#productos" class="py-1 transition-colors hover:text-[#192315]">Catálogo</a>
                <a href="#preguntas" class="py-1 transition-colors hover:text-[#192315]">Preguntas</a>
                <a href="#contacto" class="py-1 transition-colors hover:text-[#192315]">Contacto</a>
            </div>

            <div class="flex items-center gap-2.5">
                <a href="{{ route('login') }}" class="inline-flex min-h-[38px] items-center gap-1.5 rounded-[2px] border border-[#192315]/22 bg-[#FAF7EF] px-3 py-1.5 text-xs font-semibold text-[#192315] transition-colors hover:border-[#192315] hover:bg-white">
                    <x-icon name="shield" class="size-3.5 text-[#A44A28]" />
                    <span class="hidden sm:inline">Panel Admin</span>
                    <span class="sm:hidden">Admin</span>
                </a>

                <a href="{{ $headerWhatsapp ? 'https://wa.me/'.$headerWhatsapp : '#contacto' }}"
                   @if($headerWhatsapp) target="_blank" rel="noopener" @endif
                   class="inline-flex min-h-[38px] items-center gap-1.5 rounded-[2px] bg-[#192315] px-3.5 py-1.5 text-xs font-semibold text-[#F4EFE4] transition-colors hover:bg-[#263822]">
                    <x-icon name="whatsapp" class="size-3.5 text-[#EAE3D2]" />
                    <span class="hidden sm:inline">WhatsApp</span>
                    <x-icon name="arrow-up-right" class="size-3.5 text-[#D8B49A]" />
                </a>
            </div>
        </nav>
        <div class="flex items-center gap-5 overflow-x-auto border-t border-[#192315]/10 px-5 py-2 text-[11px] font-semibold text-[#595548] md:hidden">
            <a href="#nosotros" class="shrink-0 transition-colors hover:text-[#192315]">Sobre nosotros</a>
            <a href="#productos" class="shrink-0 transition-colors hover:text-[#192315]">Catálogo</a>
            <a href="#preguntas" class="shrink-0 transition-colors hover:text-[#192315]">Preguntas</a>
            <a href="#contacto" class="shrink-0 transition-colors hover:text-[#192315]">Contacto</a>
        </div>
    </header>

    @if(session('contact_sent'))
        <div role="status" data-toast class="fixed right-5 top-20 z-50 max-w-md overflow-hidden rounded-[2px] border border-[#192315]/20 bg-[#FAF7EF] p-4 text-sm text-[#192315] shadow-lg transition duration-300">
            <div class="flex items-start gap-3">
                <span class="grid size-8 shrink-0 place-items-center rounded-[2px] bg-[#192315] text-[#F4EFE4]">
                    <x-icon name="check-circle" class="size-4" />
                </span>
                <p class="flex-1 text-xs font-semibold leading-5">{{ session('contact_sent') }}</p>
                <button type="button" data-toast-close class="text-[#595548] hover:text-[#192315]" aria-label="Cerrar">
                    <x-icon name="x" class="size-4" />
                </button>
            </div>
            <div class="toast-progress mt-3 h-[2px] w-full bg-[#A44A28]"></div>
        </div>
    @endif

    @yield('content')

    <footer class="border-t border-[#192315]/15 bg-[#192315] px-5 py-14 text-[#EAE3D2] sm:px-8">
        <div class="mx-auto grid max-w-7xl gap-10 md:grid-cols-[1.25fr_.85fr_.9fr]">
            <div>
                <div class="inline-flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-[2px] border border-white/15 bg-[#263822] text-[#F4EFE4]">
                        <x-icon name="sprout" class="size-5" />
                    </span>
                    <div>
                        <p class="font-editorial text-xl font-medium text-[#F4EFE4]">Bodega Los Olivos</p>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-[#D8B49A]">Despensa de barrio · Perú</p>
                    </div>
                </div>
                <p class="mt-4 max-w-sm text-xs leading-6 text-[#C7C5B8]">
                    Abastecimiento diario, productos seleccionados y trato directo para tu hogar y negocio.
                </p>
            </div>

            <div class="text-xs">
                <p class="font-semibold uppercase tracking-[0.18em] text-[#D8B49A]">Secciones</p>
                <ul class="mt-4 space-y-2.5 text-[#D6D3C5]">
                    <li><a href="#nosotros" class="transition-colors hover:text-white">Sobre nosotros</a></li>
                    <li><a href="#productos" class="transition-colors hover:text-white">Catálogo disponible</a></li>
                    <li><a href="#preguntas" class="transition-colors hover:text-white">Preguntas frecuentes</a></li>
                    <li><a href="#contacto" class="transition-colors hover:text-white">Contacto</a></li>
                </ul>
            </div>

            <div class="text-xs">
                <p class="font-semibold uppercase tracking-[0.18em] text-[#D8B49A]">Gestión interna</p>
                <p class="mt-4 leading-6 text-[#C7C5B8]">Control de inventario, ventas, clientes, proveedores y editor web en tiempo real.</p>
                <div class="mt-5">
                    <a href="{{ route('login') }}" class="inline-flex min-h-[38px] items-center gap-2 rounded-[2px] border border-[#F4EFE4]/25 bg-[#F4EFE4]/10 px-4 py-2 text-xs font-semibold text-[#F4EFE4] transition-colors hover:bg-[#F4EFE4] hover:text-[#192315]">
                        <x-icon name="shield" class="size-3.5" />
                        Acceso administrador
                    </a>
                </div>
            </div>
        </div>

        <div class="mx-auto mt-12 flex max-w-7xl flex-col items-center justify-between gap-3 border-t border-white/12 pt-6 text-[11px] text-[#9E9D90] sm:flex-row">
            <p>© {{ date('Y') }} Bodega Los Olivos · Atención diaria de barrio</p>
            <p>Operado con <span class="font-semibold text-[#EAE3D2]">NEXO Bodega</span></p>
        </div>
    </footer>

    <x-public-chatbot
        :sections="$sections ?? collect()"
        :products="$products ?? collect()"
        :categories="$categories ?? collect()"
    />
</body>
</html>
