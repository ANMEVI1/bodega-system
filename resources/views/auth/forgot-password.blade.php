@extends('layouts.auth')
@section('title', 'Recuperar acceso')
@section('content')
    <p class="text-[11px] font-extrabold uppercase tracking-[.22em] text-fuchsia-300">Recuperación segura</p>
    <h1 class="mt-2.5 text-2xl font-extrabold tracking-tight text-white">Restablece tu contraseña</h1>
    <p class="mt-2 text-xs leading-6 text-zinc-400">Te enviaremos un enlace de verificación al correo asociado a tu cuenta administradora.</p>

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
        @csrf
        <label class="block text-xs font-bold text-zinc-300">
            Correo electrónico
            <input class="field mt-2" type="email" name="email" value="{{ old('email') }}" placeholder="admin@bodegalosolivos.pe" required autofocus>
        </label>
        <button class="btn-primary w-full">
            <x-icon name="mail" class="size-4" />
            <span>Enviar enlace de recuperación</span>
        </button>
    </form>

    <a href="{{ route('login') }}" class="mt-6 inline-flex w-full items-center justify-center gap-1.5 text-xs font-semibold text-zinc-400 transition hover:text-white">
        <x-icon name="arrow-left" class="size-3.5" />
        Volver a iniciar sesión
    </a>
@endsection
