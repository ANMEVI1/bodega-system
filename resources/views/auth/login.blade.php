@extends('layouts.auth')
@section('title', 'Iniciar sesión')
@section('content')
    <p class="text-[11px] font-extrabold uppercase tracking-[.22em] text-fuchsia-300">Bienvenida de vuelta</p>
    <h1 class="mt-2.5 text-2xl font-extrabold tracking-tight text-white">Acceso administrador</h1>
    <p class="mt-2 text-xs leading-6 text-zinc-400">Administra tu inventario, clientes, proveedores y ventas desde un solo lugar.</p>

    <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
        @csrf
        <label class="block text-xs font-bold text-zinc-300">
            Correo electrónico
            <input class="field mt-2" type="email" name="email" value="{{ old('email') }}" autocomplete="username" placeholder="admin@tubodega.pe" required autofocus>
        </label>

        <div data-password-field>
            <label class="block text-xs font-bold text-zinc-300">Contraseña</label>
            <div class="relative mt-2">
                <input class="field pr-11" type="password" name="password" autocomplete="current-password" placeholder="••••••••••••" required>
                <button type="button" data-toggle-password aria-label="Mostrar u ocultar contraseña"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-zinc-400 transition hover:bg-white/[.06] hover:text-white">
                    <span data-eye-open><x-icon name="eye" class="size-4" /></span>
                    <span data-eye-closed class="hidden"><x-icon name="eye-off" class="size-4" /></span>
                </button>
            </div>
        </div>

        <div class="flex items-center justify-between pt-1">
            <label class="flex items-center gap-2 text-xs text-zinc-400">
                <input type="checkbox" name="remember" value="1" class="size-4 rounded accent-fuchsia-500">
                Mantener sesión iniciada
            </label>
            <a href="{{ route('password.request') }}" class="text-xs font-semibold text-zinc-400 transition hover:text-fuchsia-300">¿Olvidaste tu clave?</a>
        </div>

        <button class="btn-primary w-full">
            <span>Entrar al panel</span>
            <x-icon name="arrow-right" class="size-4" />
        </button>
    </form>

    @if(config('services.google.client_id') && config('services.google.client_secret'))
        <div class="my-5 flex items-center gap-3 text-[10px] font-bold uppercase tracking-widest text-zinc-600">
            <span class="h-px flex-1 bg-white/[.08]"></span>
            <span>o continúa con</span>
            <span class="h-px flex-1 bg-white/[.08]"></span>
        </div>

        <a href="{{ route('google.redirect') }}" class="btn-subtle w-full text-xs font-bold">
            <x-icon name="google" class="size-4" />
            <span>Continuar con Google</span>
        </a>
    @endif
@endsection
