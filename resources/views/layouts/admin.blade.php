<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') · NEXO Bodega</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,SOFT,WONK,wght@0,9..144,0..100,0..1,600..900;1,9..144,0..100,0..1,400..800&family=JetBrains+Mono:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>document.documentElement.classList.add('js-cinema');</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-cinema min-h-screen antialiased bg-[#050508] text-zinc-100">
    {{-- Atmospheric backdrop layers --}}
    <div class="fixed inset-0 -z-20 cinema-grid print:hidden"></div>
    <div class="fixed inset-0 z-50 cinema-grain print:hidden"></div>
    <div class="ambient fixed -left-48 -top-52 -z-10 size-[36rem] rounded-full blur-[130px] print:hidden"></div>
    <div class="ambient-amber fixed -bottom-52 -right-40 -z-10 size-[32rem] rounded-full blur-[130px] opacity-60 print:hidden"></div>

    @php
        $navItems = [
            ['admin.dashboard', 'Inicio', 'home', null],
            ['admin.clients.index', 'Clientes', 'users', null],
            ['admin.products.index', 'Productos', 'shopping-bag', null],
            ['admin.categories.index', 'Categorías', 'tag', null],
            ['admin.inventory.index', 'Inventario', 'layers', ($navLowStock ?? 0) > 0 ? $navLowStock : null],
            ['admin.suppliers.index', 'Proveedores', 'truck', null],
            ['admin.sales.index', 'Ventas y finanzas', 'trending-up', null],
            ['admin.contacts.index', 'Consultas web', 'message', ($navNewContacts ?? 0) > 0 ? $navNewContacts : null],
            ['admin.site.edit', 'Editar página web', 'globe', null],
        ];
    @endphp

    <div class="relative min-h-screen lg:pl-[272px]">
        {{-- Desktop Sidebar --}}
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-[272px] flex-col border-r border-white/[.07] bg-[#08080c]/85 px-4 py-6 backdrop-blur-2xl lg:flex print:hidden">
            <a href="{{ route('admin.dashboard') }}" class="group flex items-center gap-3.5 px-2.5">
                <span class="relative grid size-11 place-items-center rounded-2xl bg-gradient-to-br from-fuchsia-500 via-purple-600 to-violet-700 text-base font-black text-white shadow-lg shadow-fuchsia-500/25 transition duration-300 group-hover:scale-105 group-hover:shadow-fuchsia-500/40">
                    N
                    <span class="absolute inset-0 rounded-2xl ring-1 ring-inset ring-white/25"></span>
                </span>
                <span>
                    <span class="block text-sm font-black tracking-tight text-white">NEXO <span class="font-semibold text-fuchsia-300">Bodega</span></span>
                    <span class="mt-0.5 block text-[10px] font-semibold text-zinc-500">Gestión inteligente</span>
                </span>
            </a>

            <p class="mb-2.5 mt-9 px-3 text-[10px] font-extrabold uppercase tracking-[.22em] text-zinc-600">Operaciones</p>
            <nav class="space-y-1">
                @foreach($navItems as [$route, $label, $icon, $badge])
                    @php
                        $active = request()->routeIs($route === 'admin.dashboard' ? $route : str_replace('.index', '.*', $route));
                    @endphp
                    <a href="{{ route($route) }}"
                       class="group relative flex items-center gap-3 rounded-2xl px-3.5 py-2.5 text-xs font-bold transition-all duration-200 {{ $active ? 'bg-gradient-to-r from-white/[.13] to-white/[.04] text-white shadow-lg shadow-black/30 ring-1 ring-white/15' : 'text-zinc-400 hover:bg-white/[.04] hover:text-zinc-100' }}">
                        @if($active)
                            <span class="absolute left-0 top-1/2 h-5 w-1 -translate-y-1/2 rounded-r-full bg-gradient-to-b from-fuchsia-400 to-violet-500"></span>
                        @endif
                        <x-icon :name="$icon" class="size-[18px] shrink-0 transition duration-200 {{ $active ? 'text-fuchsia-300' : 'text-zinc-500 group-hover:text-zinc-200' }}" />
                        <span class="flex-1 truncate">{{ $label }}</span>
                        @if($badge)
                            <span class="font-mono-num rounded-full bg-fuchsia-500/20 px-2 py-0.5 text-[10px] font-bold text-fuchsia-200 ring-1 ring-fuchsia-400/25">{{ $badge }}</span>
                        @endif
                    </a>
                @endforeach

                <a href="{{ route('admin.dashboard') }}#asistente"
                   class="group flex items-center gap-3 rounded-2xl px-3.5 py-2.5 text-xs font-bold text-zinc-400 transition-all duration-200 hover:bg-fuchsia-500/[.08] hover:text-fuchsia-200">
                    <x-icon name="sparkles" class="size-[18px] shrink-0 text-fuchsia-400 transition duration-300 group-hover:rotate-12" />
                    <span class="flex-1">Asistente NIA</span>
                    <span class="rounded-full bg-emerald-400/10 px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-wider text-emerald-300">Local</span>
                </a>
            </nav>

            <div class="mt-auto space-y-3 pt-4">
                <a href="{{ route('home') }}" target="_blank"
                   class="group flex items-center justify-between rounded-2xl border border-amber-300/15 bg-gradient-to-r from-amber-400/[.08] to-orange-500/[.04] px-4 py-3 text-xs font-bold text-amber-100 transition hover:border-amber-300/30 hover:from-amber-400/[.14]">
                    <span class="flex items-center gap-2.5">
                        <x-icon name="store" class="size-4 text-amber-300" />
                        <span>Ver página pública</span>
                    </span>
                    <x-icon name="arrow-up-right" class="size-4 text-amber-300/80 transition group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                </a>

                <div class="flex items-center gap-3 rounded-2xl border border-white/[.08] bg-white/[.03] p-3">
                    <span class="grid size-9 shrink-0 place-items-center rounded-full bg-gradient-to-br from-fuchsia-500 to-violet-600 text-xs font-black text-white shadow-inner ring-1 ring-white/20">
                        {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-bold text-white">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] font-medium text-zinc-500">Administrador</p>
                    </div>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" title="Cerrar sesión" aria-label="Cerrar sesión" class="grid size-8 place-items-center rounded-xl text-zinc-400 transition hover:bg-white/[.08] hover:text-white">
                            <x-icon name="log-out" class="size-4" />
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Mobile Slide-Over Drawer --}}
        <div data-mobile-drawer class="fixed inset-0 z-50 hidden flex-col bg-black/75 backdrop-blur-md lg:hidden print:hidden">
            <div class="mt-auto rounded-t-[2rem] border-t border-white/10 bg-[#0d0d12] p-6 shadow-2xl">
                <div class="mb-5 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="grid size-9 place-items-center rounded-xl bg-gradient-to-br from-fuchsia-500 to-violet-600 text-xs font-black">N</span>
                        <div>
                            <p class="text-sm font-extrabold text-white">{{ auth()->user()->name }}</p>
                            <p class="text-[11px] text-zinc-500">NEXO Bodega · Menú</p>
                        </div>
                    </div>
                    <button type="button" data-drawer-close class="grid size-9 place-items-center rounded-full border border-white/10 bg-white/[.05] text-zinc-300" aria-label="Cerrar menú">
                        <x-icon name="x" class="size-4" />
                    </button>
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    @foreach($navItems as [$route, $label, $icon, $badge])
                        <a href="{{ route($route) }}" class="flex items-center gap-2.5 rounded-2xl border border-white/[.07] bg-white/[.03] px-3.5 py-3 text-xs font-bold text-zinc-200">
                            <x-icon :name="$icon" class="size-4 text-fuchsia-300" />
                            <span class="truncate">{{ $label }}</span>
                        </a>
                    @endforeach
                    <a href="{{ route('admin.dashboard') }}#asistente" data-drawer-close class="flex items-center gap-2.5 rounded-2xl border border-white/[.07] bg-white/[.03] px-3.5 py-3 text-xs font-bold text-zinc-200">
                        <x-icon name="sparkles" class="size-4 text-fuchsia-300" />
                        <span>Asistente NIA</span>
                    </a>
                </div>
                <div class="mt-4 flex items-center gap-3 border-t border-white/[.07] pt-4">
                    <a href="{{ route('home') }}" target="_blank" class="btn-subtle flex-1 text-xs">
                        <x-icon name="store" class="size-4 text-amber-300" />
                        Ver sitio
                    </a>
                    <form method="POST" action="{{ route('admin.logout') }}" class="flex-1">
                        @csrf
                        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-2xl border border-rose-400/20 bg-rose-500/10 px-4 py-2.5 text-xs font-bold text-rose-200">
                            <x-icon name="log-out" class="size-4" />
                            Cerrar sesión
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Top Sticky Header --}}
        <header class="sticky top-0 z-20 flex items-center justify-between border-b border-white/[.06] bg-[#07070b]/80 px-5 py-3.5 backdrop-blur-2xl lg:px-10 print:hidden">
            <div class="flex items-center gap-3">
                <span class="grid size-9 place-items-center rounded-xl bg-gradient-to-br from-fuchsia-500 to-violet-600 text-xs font-black shadow-md shadow-fuchsia-500/20 lg:hidden">N</span>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[.16em] text-zinc-500">Panel de control</p>
                    <p class="mt-0.5 text-sm font-extrabold tracking-tight text-white">@yield('heading', 'Tu negocio hoy')</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="hidden items-center gap-2 rounded-full border border-emerald-400/15 bg-emerald-400/[.06] px-3.5 py-1.5 text-[11px] font-bold text-emerald-200 sm:inline-flex">
                    <span class="status-ping size-2 rounded-full bg-emerald-400"></span>
                    Operación al día
                </span>
                <a href="{{ route('home') }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/[.04] px-3 py-1.5 text-xs font-semibold text-zinc-300 transition hover:bg-white/[.08] lg:hidden">
                    <x-icon name="store" class="size-3.5 text-amber-300" />
                    Sitio
                </a>
                <button type="button" data-drawer-open class="grid size-9 place-items-center rounded-xl border border-white/10 bg-white/[.04] text-zinc-200 lg:hidden" aria-label="Abrir menú">
                    <x-icon name="menu" class="size-4" />
                </button>
            </div>
        </header>

        {{-- Main Content --}}
        <main class="relative mx-auto max-w-[1480px] px-4 pb-28 pt-7 sm:px-7 lg:px-10 lg:pb-12 lg:pt-9">
            @if(session('success'))
                <div role="status" data-toast class="mb-6 overflow-hidden rounded-2xl border border-emerald-400/20 bg-emerald-500/[.09] px-4 py-3.5 text-sm text-emerald-100 shadow-lg transition duration-300">
                    <div class="flex items-center gap-3">
                        <x-icon name="check-circle" class="size-5 shrink-0 text-emerald-400" />
                        <span class="flex-1 text-xs font-semibold sm:text-sm">{{ session('success') }}</span>
                        <button type="button" data-toast-close class="text-emerald-200/70 hover:text-white" aria-label="Cerrar">
                            <x-icon name="x" class="size-4" />
                        </button>
                    </div>
                    <div class="toast-progress mt-2.5 h-0.5 w-full rounded-full bg-emerald-400/70"></div>
                </div>
            @endif

            @if($errors->any())
                <div role="alert" class="mb-6 rounded-2xl border border-rose-400/25 bg-rose-500/[.09] px-4 py-3.5 text-sm text-rose-100 shadow-lg">
                    <div class="flex items-start gap-3">
                        <x-icon name="alert-triangle" class="mt-0.5 size-5 shrink-0 text-rose-400" />
                        <div>
                            <p class="text-xs font-extrabold uppercase tracking-wider text-rose-200">Revisa estos datos</p>
                            <ul class="mt-1.5 list-inside list-disc space-y-0.5 text-xs">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>

        {{-- Mobile Bottom Navigation --}}
        <nav class="fixed inset-x-0 bottom-0 z-30 grid grid-cols-5 border-t border-white/[.08] bg-[#09090d]/95 px-1 pb-[env(safe-area-inset-bottom)] pt-1.5 backdrop-blur-2xl lg:hidden print:hidden">
            @foreach([
                ['admin.dashboard', 'Inicio', 'home'],
                ['admin.clients.index', 'Clientes', 'users'],
                ['admin.products.index', 'Stock', 'package'],
                ['admin.sales.index', 'Ventas', 'trending-up'],
            ] as [$route, $label, $icon])
                @php
                    $active = request()->routeIs($route === 'admin.dashboard' ? $route : str_replace('.index', '.*', $route));
                @endphp
                <a href="{{ route($route) }}" class="flex flex-col items-center gap-1 rounded-xl px-1 py-2 text-[10px] font-bold transition {{ $active ? 'text-fuchsia-300' : 'text-zinc-500 hover:text-zinc-200' }}">
                    <x-icon :name="$icon" class="size-5" />
                    <span>{{ $label }}</span>
                </a>
            @endforeach
            <button type="button" data-drawer-open class="flex flex-col items-center gap-1 rounded-xl px-1 py-2 text-[10px] font-bold text-zinc-500 hover:text-zinc-200">
                <x-icon name="menu" class="size-5" />
                <span>Más</span>
            </button>
        </nav>
    </div>

    {{-- Custom Cinematic Confirmation Modal --}}
    <div id="cinema-confirm-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/75 p-4 backdrop-blur-md print:hidden" role="dialog" aria-modal="true">
        <div class="panel w-full max-w-sm rounded-3xl p-6 shadow-2xl" data-cinema-enter>
            <div class="flex items-start gap-3.5">
                <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-rose-500/15 text-rose-300 ring-1 ring-rose-400/25">
                    <x-icon name="alert-triangle" class="size-5" />
                </span>
                <div>
                    <h3 data-confirm-title class="text-base font-extrabold text-white">Confirmar eliminación</h3>
                    <p data-confirm-body class="mt-1.5 text-xs leading-5 text-zinc-400">¿Deseas continuar con esta acción?</p>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-2.5">
                <button type="button" data-confirm-cancel class="btn-subtle text-xs">Cancelar</button>
                <button type="button" data-confirm-accept class="inline-flex items-center gap-1.5 rounded-2xl bg-gradient-to-r from-rose-500 to-pink-600 px-4 py-2.5 text-xs font-extrabold text-white shadow-lg shadow-rose-500/25 transition hover:brightness-110">
                    <x-icon name="trash" class="size-3.5" />
                    Sí, eliminar
                </button>
            </div>
        </div>
    </div>
</body>
</html>
