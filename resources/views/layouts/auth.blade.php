<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Acceso') · NEXO Bodega</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,SOFT,WONK,wght@0,9..144,0..100,0..1,600..900;1,9..144,0..100,0..1,400..800&family=JetBrains+Mono:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>document.documentElement.classList.add('js-cinema');</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-cinema relative grid min-h-screen place-items-center overflow-x-hidden bg-[#050508] px-4 py-10 antialiased text-zinc-100">
    <div class="fixed inset-0 -z-20 cinema-grid"></div>
    <div class="fixed inset-0 z-50 cinema-grain"></div>
    <div class="ambient fixed -left-40 -top-40 -z-10 size-[38rem] rounded-full blur-[130px]"></div>
    <div class="ambient-amber fixed -bottom-44 -right-36 -z-10 size-[32rem] rounded-full blur-[130px] opacity-70"></div>

    <div class="relative w-full max-w-[440px]" data-cinema-enter>
        <div class="absolute -inset-1 rounded-[2.2rem] bg-gradient-to-b from-fuchsia-500/20 via-violet-500/5 to-transparent blur-xl"></div>
        <main class="panel spotlight-card relative rounded-[2rem] p-7 sm:p-9">
            <div class="mb-7 flex items-center justify-between">
                <a href="{{ route('home') }}" class="group inline-flex items-center gap-3">
                    <span class="relative grid size-11 place-items-center rounded-2xl bg-gradient-to-br from-fuchsia-500 via-purple-600 to-violet-700 text-base font-black text-white shadow-lg shadow-fuchsia-500/25 transition duration-300 group-hover:scale-105">
                        N
                        <span class="absolute inset-0 rounded-2xl ring-1 ring-inset ring-white/25"></span>
                    </span>
                    <span>
                        <span class="block text-sm font-black tracking-tight text-white">NEXO <span class="font-semibold text-fuchsia-300">Bodega</span></span>
                        <span class="block text-[10px] font-medium text-zinc-500">Gestión de bodega</span>
                    </span>
                </a>
                <a href="{{ route('home') }}" class="inline-flex items-center gap-1 rounded-full border border-white/10 bg-white/[.03] px-3 py-1.5 text-[11px] font-semibold text-zinc-400 transition hover:border-white/20 hover:text-white">
                    <x-icon name="arrow-left" class="size-3" />
                    Tienda
                </a>
            </div>

            @if(session('status'))
                <div class="mb-5 flex items-start gap-2.5 rounded-2xl border border-emerald-400/25 bg-emerald-500/10 p-3.5 text-xs leading-5 text-emerald-100">
                    <x-icon name="check-circle" class="mt-0.5 size-4 shrink-0 text-emerald-400" />
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div role="alert" class="mb-5 flex items-start gap-2.5 rounded-2xl border border-rose-400/25 bg-rose-500/10 p-3.5 text-xs leading-5 text-rose-100">
                    <x-icon name="alert-triangle" class="mt-0.5 size-4 shrink-0 text-rose-400" />
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
