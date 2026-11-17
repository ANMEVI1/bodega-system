@extends('layouts.auth')
@section('title', 'Nueva contraseña')
@section('content')
    <p class="text-[11px] font-extrabold uppercase tracking-[.22em] text-fuchsia-300">Verificación de identidad</p>
    <h1 class="mt-2.5 text-2xl font-extrabold tracking-tight text-white">Crea una nueva contraseña</h1>
    <p class="mt-2 text-xs leading-6 text-zinc-400">Define una contraseña robusta para proteger el panel de tu bodega.</p>

    <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <label class="block text-xs font-bold text-zinc-300">
            Correo electrónico
            <input class="field mt-2" type="email" name="email" value="{{ old('email', $email) }}" required>
        </label>

        <div data-password-field>
            <label class="block text-xs font-bold text-zinc-300">Nueva contraseña</label>
            <div class="relative mt-2">
                <input class="field pr-11" type="password" name="password" minlength="12" data-password-strength="reset-strength" required>
                <button type="button" data-toggle-password aria-label="Mostrar u ocultar contraseña"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-zinc-400 transition hover:bg-white/[.06] hover:text-white">
                    <span data-eye-open><x-icon name="eye" class="size-4" /></span>
                    <span data-eye-closed class="hidden"><x-icon name="eye-off" class="size-4" /></span>
                </button>
            </div>
            <div id="reset-strength" class="mt-2.5 space-y-2">
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
            <label class="block text-xs font-bold text-zinc-300">Confirma la contraseña</label>
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
            <span>Actualizar contraseña</span>
            <x-icon name="arrow-right" class="size-4" />
        </button>
    </form>
@endsection
