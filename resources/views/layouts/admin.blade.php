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
    <div class="ambient fixed -left-48 -top-52 -z-10 size-144 rounded-full blur-[130px] print:hidden"></div>
    <div class="ambient-amber fixed -bottom-52 -right-40 -z-10 size-128 rounded-full blur-[130px] opacity-60 print:hidden"></div>

    @php
        $adminName = auth()->user()?->name ?? 'Administrador';
        $adminInitial = mb_strtoupper(mb_substr($adminName, 0, 1));
        $criticalStockCount = \Illuminate\Support\Facades\Schema::hasTable('products')
            ? \App\Models\Product::query()
                ->where('active', true)
                ->where(fn ($query) => $query->whereColumn('stock', '<=', 'low_stock_at')->orWhere('stock', '<=', 5))
                ->count()
            : (int) ($navLowStock ?? 0);
        $navItems = [
            ['route' => 'admin.dashboard', 'label' => 'Inicio', 'icon' => 'home', 'badge' => null],
            ['route' => 'admin.clients.index', 'label' => 'Clientes', 'icon' => 'users', 'badge' => null],
            ['route' => 'admin.products.index', 'label' => 'Productos', 'icon' => 'shopping-bag', 'badge' => null],
            ['route' => 'admin.categories.index', 'label' => 'Categorías', 'icon' => 'tag', 'badge' => null],
            ['route' => 'admin.inventory.index', 'label' => 'Inventario', 'icon' => 'layers', 'badge' => ($navLowStock ?? 0) > 0 ? $navLowStock : null],
            ['route' => 'admin.suppliers.index', 'label' => 'Proveedores', 'icon' => 'truck', 'badge' => null],
            ['route' => 'admin.sales.index', 'label' => 'Ventas y finanzas', 'icon' => 'trending-up', 'badge' => null],
            ['route' => 'admin.contacts.index', 'label' => 'Consultas web', 'icon' => 'message', 'badge' => ($navNewContacts ?? 0) > 0 ? $navNewContacts : null],
            ['route' => 'admin.site.edit', 'label' => 'Editar página web', 'icon' => 'globe', 'badge' => null],
        ];
        $mobileNavItems = [
            ['route' => 'admin.dashboard', 'label' => 'Inicio', 'icon' => 'home'],
            ['route' => 'admin.clients.index', 'label' => 'Clientes', 'icon' => 'users'],
            ['route' => 'admin.inventory.index', 'label' => 'Stock', 'icon' => 'layers'],
            ['route' => 'admin.sales.index', 'label' => 'Ventas', 'icon' => 'trending-up'],
        ];
    @endphp

    <div class="relative min-h-screen lg:pl-68">
        {{-- Desktop Sidebar --}}
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-68 flex-col border-r border-white/7 bg-[#08080c]/85 px-4 py-6 backdrop-blur-2xl lg:flex print:hidden">
            <a href="{{ route('admin.dashboard') }}" class="group flex items-center gap-3.5 px-2.5">
                <span class="relative grid size-11 place-items-center rounded-2xl bg-linear-to-br from-fuchsia-500 via-purple-600 to-violet-700 text-base font-black text-white shadow-lg shadow-fuchsia-500/25 transition duration-300 group-hover:scale-105 group-hover:shadow-fuchsia-500/40">
                    N
                    <span class="absolute inset-0 rounded-2xl ring-1 ring-inset ring-white/25"></span>
                </span>
                <span>
                    <span class="block text-sm font-black tracking-tight text-white">NEXO <span class="font-semibold text-fuchsia-300">Bodega</span></span>
                    <span class="mt-0.5 block text-[10px] font-semibold text-zinc-500">Gestión inteligente</span>
                </span>
            </a>

            <p class="mb-2.5 mt-9 px-3 text-[10px] font-extrabold uppercase tracking-[.22em] text-zinc-600">Operaciones</p>
            <nav aria-label="Navegación principal" class="space-y-1">
                @foreach($navItems as $item)
                    @php
                        $active = request()->routeIs($item['route'] === 'admin.dashboard' ? $item['route'] : str_replace('.index', '.*', $item['route']));
                    @endphp
                    <a href="{{ route($item['route']) }}"
                       class="group relative flex items-center gap-3 rounded-2xl px-3.5 py-2.5 text-xs font-bold transition-all duration-200 {{ $active ? 'bg-linear-to-r from-white/13 to-white/4 text-white shadow-lg shadow-black/30 ring-1 ring-white/15' : 'text-zinc-400 hover:bg-white/4 hover:text-zinc-100' }}">
                        @if($active)
                            <span class="absolute left-0 top-1/2 h-5 w-1 -translate-y-1/2 rounded-r-full bg-linear-to-b from-fuchsia-400 to-violet-500"></span>
                        @endif
                        <x-icon :name="$item['icon']" class="size-4.5 shrink-0 transition duration-200 {{ $active ? 'text-fuchsia-300' : 'text-zinc-500 group-hover:text-zinc-200' }}" />
                        <span class="flex-1 truncate">{{ $item['label'] }}</span>
                        @if($item['badge'])
                            <span class="font-mono-num rounded-full bg-fuchsia-500/20 px-2 py-0.5 text-[10px] font-bold text-fuchsia-200 ring-1 ring-fuchsia-400/25">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <div class="mt-auto space-y-3 pt-4">
                <a href="{{ route('home') }}" target="_blank" rel="noopener noreferrer"
                   class="group flex items-center justify-between rounded-2xl border border-amber-300/15 bg-linear-to-r from-amber-400/8 to-orange-500/4 px-4 py-3 text-xs font-bold text-amber-100 transition hover:border-amber-300/30 hover:from-amber-400/14">
                    <span class="flex items-center gap-2.5">
                        <x-icon name="store" class="size-4 text-amber-300" />
                        <span>Ver página pública</span>
                    </span>
                    <x-icon name="arrow-up-right" class="size-4 text-amber-300/80 transition group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                </a>

                <div class="flex items-center gap-3 rounded-2xl border border-white/8 bg-white/3 p-3">
                    <span class="grid size-9 shrink-0 place-items-center rounded-full bg-linear-to-br from-fuchsia-500 to-violet-600 text-xs font-black text-white shadow-inner ring-1 ring-white/20">
                        {{ $adminInitial }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-bold text-white">{{ $adminName }}</p>
                        <p class="text-[10px] font-medium text-zinc-500">Administrador</p>
                    </div>
                    <form method="POST" action="{{ route('admin.logout') }}" data-nia-logout>
                        @csrf
                        <button type="submit" title="Cerrar sesión" aria-label="Cerrar sesión" class="grid size-8 place-items-center rounded-xl text-zinc-400 transition hover:bg-white/8 hover:text-white">
                            <x-icon name="log-out" class="size-4" />
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Mobile Slide-Over Drawer --}}
        <div data-mobile-drawer class="fixed inset-0 z-50 hidden flex-col bg-black/75 backdrop-blur-md lg:hidden print:hidden">
            <div class="mt-auto rounded-t-4xl border-t border-white/10 bg-[#0d0d12] p-6 shadow-2xl">
                <div class="mb-5 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="grid size-9 place-items-center rounded-xl bg-linear-to-br from-fuchsia-500 to-violet-600 text-xs font-black">N</span>
                        <div>
                            <p class="text-sm font-extrabold text-white">{{ $adminName }}</p>
                            <p class="text-[11px] text-zinc-500">NEXO Bodega · Menú</p>
                        </div>
                    </div>
                    <button type="button" data-drawer-close class="grid size-9 place-items-center rounded-full border border-white/10 bg-white/5 text-zinc-300" aria-label="Cerrar menú">
                        <x-icon name="x" class="size-4" />
                    </button>
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    @foreach($navItems as $item)
                        <a href="{{ route($item['route']) }}" class="flex items-center gap-2.5 rounded-2xl border border-white/7 bg-white/3 px-3.5 py-3 text-xs font-bold text-zinc-200">
                            <x-icon :name="$item['icon']" class="size-4 text-fuchsia-300" />
                            <span class="truncate">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
                <div class="mt-4 flex items-center gap-3 border-t border-white/7 pt-4">
                    <a href="{{ route('home') }}" target="_blank" rel="noopener noreferrer" class="btn-subtle flex-1 text-xs">
                        <x-icon name="store" class="size-4 text-amber-300" />
                        Ver sitio
                    </a>
                    <form method="POST" action="{{ route('admin.logout') }}" data-nia-logout class="flex-1">
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
        <header class="sticky top-0 z-20 flex items-center justify-between border-b border-white/6 bg-[#07070b]/80 px-5 py-3.5 backdrop-blur-2xl lg:px-10 print:hidden">
            <div class="flex items-center gap-3">
                <span class="grid size-9 place-items-center rounded-xl bg-linear-to-br from-fuchsia-500 to-violet-600 text-xs font-black shadow-md shadow-fuchsia-500/20 lg:hidden">N</span>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[.16em] text-zinc-500">Panel de control</p>
                    <p class="mt-0.5 text-sm font-extrabold tracking-tight text-white">@yield('heading', 'Tu negocio hoy')</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="hidden items-center gap-2 rounded-full border border-emerald-400/15 bg-emerald-400/6 px-3.5 py-1.5 text-[11px] font-bold text-emerald-200 sm:inline-flex">
                    <span class="status-ping size-2 rounded-full bg-emerald-400"></span>
                    Operación al día
                </span>
                <a href="{{ route('home') }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/4 px-3 py-1.5 text-xs font-semibold text-zinc-300 transition hover:bg-white/8 lg:hidden">
                    <x-icon name="store" class="size-3.5 text-amber-300" />
                    Sitio
                </a>
                <button type="button" data-drawer-open class="grid size-9 place-items-center rounded-xl border border-white/10 bg-white/4 text-zinc-200 lg:hidden" aria-label="Abrir menú">
                    <x-icon name="menu" class="size-4" />
                </button>
            </div>
        </header>

        {{-- Main Content --}}
        <main class="relative mx-auto max-w-370 px-4 pb-28 pt-7 sm:px-7 lg:px-10 lg:pb-12 lg:pt-9">
            @if(session('success'))
                <div role="status" data-toast class="mb-6 overflow-hidden rounded-2xl border border-emerald-400/20 bg-emerald-500/9 px-4 py-3.5 text-sm text-emerald-100 shadow-lg transition duration-300">
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
                <div role="alert" class="mb-6 rounded-2xl border border-rose-400/25 bg-rose-500/9 px-4 py-3.5 text-sm text-rose-100 shadow-lg">
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
        <nav aria-label="Navegación móvil" class="fixed inset-x-0 bottom-0 z-30 grid grid-cols-5 border-t border-white/8 bg-[#09090d]/95 px-1 pb-[env(safe-area-inset-bottom)] pt-1.5 backdrop-blur-2xl lg:hidden print:hidden">
            @foreach($mobileNavItems as $item)
                @php
                    $active = request()->routeIs($item['route'] === 'admin.dashboard' ? $item['route'] : str_replace('.index', '.*', $item['route']));
                @endphp
                <a href="{{ route($item['route']) }}" class="flex flex-col items-center gap-1 rounded-xl px-1 py-2 text-[10px] font-bold transition {{ $active ? 'text-fuchsia-300' : 'text-zinc-500 hover:text-zinc-200' }}">
                    <x-icon :name="$item['icon']" class="size-5" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
            <button type="button" data-drawer-open class="flex flex-col items-center gap-1 rounded-xl px-1 py-2 text-[10px] font-bold text-zinc-500 hover:text-zinc-200">
                <x-icon name="menu" class="size-5" />
                <span>Más</span>
            </button>
        </nav>
    </div>

    {{-- Custom Cinematic Confirmation Modal --}}
    <div id="cinema-confirm-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/75 p-4 backdrop-blur-md print:hidden" role="dialog" aria-modal="true" aria-labelledby="cinema-confirm-title" aria-describedby="cinema-confirm-body">
        <div class="panel w-full max-w-sm rounded-3xl p-6 shadow-2xl" data-cinema-enter>
            <div class="flex items-start gap-3.5">
                <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-rose-500/15 text-rose-300 ring-1 ring-rose-400/25">
                    <x-icon name="alert-triangle" class="size-5" />
                </span>
                <div>
                    <h3 id="cinema-confirm-title" data-confirm-title class="text-base font-extrabold text-white">Confirmar eliminación</h3>
                    <p id="cinema-confirm-body" data-confirm-body class="mt-1.5 text-xs leading-5 text-zinc-400">¿Deseas continuar con esta acción?</p>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-2.5">
                <button type="button" data-confirm-cancel class="btn-subtle text-xs">Cancelar</button>
                <button type="button" data-confirm-accept class="inline-flex items-center gap-1.5 rounded-2xl bg-linear-to-r from-rose-500 to-pink-600 px-4 py-2.5 text-xs font-extrabold text-white shadow-lg shadow-rose-500/25 transition hover:brightness-110">
                    <x-icon name="trash" class="size-3.5" />
                    Sí, eliminar
                </button>
            </div>
        </div>
    </div>

    <x-nia-chatbot :low-stock-count="$criticalStockCount" />
</body>
</html>
