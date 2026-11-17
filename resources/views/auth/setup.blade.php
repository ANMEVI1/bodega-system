@extends('layouts.auth')
@section('title', 'Crear administrador')
@section('content')
    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-[10px] font-extrabold uppercase tracking-[.18em] text-emerald-200">
        <span class="size-1.5 rounded-full bg-emerald-400"></span>
        Primer ingreso · Paso único para entrar al panel
    </span>
    <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-white">Crea tu cuenta administradora</h1>
    <p class="mt-2 text-xs leading-6 text-zinc-400">Al registrarte entrarás directamente a <span class="font-mono-num text-zinc-200">/admin</span>. Luego este formulario se cierra por seguridad.</p>

    <form method="POST" action="{{ route('setup.store') }}" class="mt-6 space-y-4">
        @csrf
        <label class="block text-xs font-bold text-zinc-300">
            Nombre completo
            <input class="field mt-2" name="name" value="{{ old('name') }}" maxlength="120" placeholder="Ej. Camila Mendoza" required autofocus>
        </label>

        <label class="block text-xs font-bold text-zinc-300">
            Correo electrónico
            <input class="field mt-2" type="email" name="email" value="{{ old('email') }}" placeholder="admin@bodegalosolivos.pe" required>
        </label>

        <div data-password-field>
            <label class="block text-xs font-bold text-zinc-300">Contraseña (mínimo 12 caracteres)</label>
            <div class="relative mt-2">
                <input class="field pr-11" type="password" name="password" minlength="12" data-password-strength="setup-strength" placeholder="Ej. BodegaSegura2026!" required>
                <button type="button" data-toggle-password aria-label="Mostrar u ocultar contraseña"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-zinc-400 transition hover:bg-white/[.06] hover:text-white">
                    <span data-eye-open><x-icon name="eye" class="size-4" /></span>
                    <span data-eye-closed class="hidden"><x-icon name="eye-off" class="size-4" /></span>
                </button>
            </div>
            <div id="setup-strength" class="mt-2.5 space-y-2">
                <div class="h-1 w-full overflow-hidden rounded-full bg-white/[.07]">
                    <div data-strength-bar class="h-full w-0 rounded-full bg-zinc-700 transition-all duration-300"></div>
                </div>
                <p data-strength-label class="text-[10px] text-zinc-500">Mínimo 12 caracteres, mayúsculas, minúsculas y números</p>
                <div class="flex flex-wrap gap-1.5 text-[10px] font-semibold">
                    <span data-rule-len class="rounded-full border border-white/10 bg-white/[.02] px-2.5 py-0.5 text-zinc-500 transition">12+ caracteres</span>
                    <span data-rule-mixed class="rounded-full border border-white/10 bg-white/[.02] px-2.5 py-0.5 text-zinc-500 transition">Mayús. y minús.</span>
                    <span data-rule-num class="rounded-full border border-white/10 bg-white/[.02] px-2.5 py-0.5 text-zinc-500 transition">Al menos 1 número</span>
                </div>
            </div>
        </div>

        <div data-password-field>
            <label class="block text-xs font-bold text-zinc-300">Confirma tu contraseña</label>
            <div class="relative mt-2">
                <input class="field pr-11" type="password" name="password_confirmation" minlength="12" required>
                <button type="button" data-toggle-password aria-label="Mostrar u ocultar confirmación"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-zinc-400 transition hover:bg-white/[.06] hover:text-white">
                    <span data-eye-open><x-icon name="eye" class="size-4" /></span>
                    <span data-eye-closed class="hidden"><x-icon name="eye-off" class="size-4" /></span>
                </button>
            </div>
        </div>

        <button class="btn-primary w-full">
            <span>Crear administrador y entrar al panel</span>
            <x-icon name="arrow-right" class="size-4" />
        </button>
    </form>
@endsection
