<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso restringido (403) · {{ config('app.name', 'NEXO Bodega') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#07070b] text-zinc-100 antialiased selection:bg-fuchsia-500/30 selection:text-fuchsia-100">
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="animate-float-slow absolute -top-32 left-1/2 h-[420px] w-[720px] -translate-x-1/2 rounded-full bg-rose-600/12 blur-[120px]"></div>
        <div class="animate-float-delayed absolute bottom-0 right-10 h-[320px] w-[420px] rounded-full bg-violet-600/12 blur-[120px]"></div>
    </div>

    @php
        $user = auth()->user();
        $fallbackRoute = $user?->firstAllowedAdminRoute() ?? 'home';
        $roleName = $user?->role?->name;
    @endphp

    <main class="flex min-h-screen items-center justify-center px-4 py-12">
        <section class="panel spotlight-card w-full max-w-lg rounded-3xl p-7 text-center sm:p-9">
            <div class="mx-auto grid size-14 place-items-center rounded-2xl border border-rose-400/25 bg-rose-500/12 text-rose-300 shadow-lg shadow-rose-500/15">
                <x-icon name="shield" class="size-7" />
            </div>

            <p class="mt-5 font-mono-num text-[11px] font-extrabold uppercase tracking-[.22em] text-rose-300">Error 403 · Matriz RBAC</p>
            <h1 class="mt-2 text-2xl font-black tracking-tight text-white">No tienes permiso para este módulo</h1>
            <p class="mt-3 text-xs leading-6 text-zinc-400">
                @if($roleName)
                    Tu rol actual (<strong class="font-bold text-zinc-200">{{ $roleName }}</strong>) no incluye autorización para acceder a esta operación.
                @else
                    Tu cuenta actual no tiene permisos RBAC para realizar esta acción.
                @endif
                Si necesitas ampliar tu acceso, solicita actualización a un Super Administrador.
            </p>

            <div class="mt-7 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route($fallbackRoute) }}" class="btn-primary text-xs">
                    <x-icon name="arrow-left" class="size-4" />
                    <span>{{ $user ? 'Ir a mi módulo autorizado' : 'Volver al inicio' }}</span>
                </a>
                <a href="{{ route('home') }}" class="btn-subtle text-xs">
                    <x-icon name="store" class="size-4 text-amber-300" />
                    <span>Ver tienda pública</span>
                </a>
            </div>
        </section>
    </main>
</body>
</html>
