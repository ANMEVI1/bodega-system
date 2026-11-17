@extends('layouts.admin')
@section('title', $client->exists ? 'Editar cliente' : 'Agregar cliente')
@section('heading', $client->exists ? 'Editar cliente' : 'Nuevo cliente')
@section('content')
    <div class="mx-auto max-w-2xl" data-cinema-enter>
        <a href="{{ route('admin.clients.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-zinc-400 transition hover:text-white">
            <x-icon name="arrow-left" class="size-4" />
            Volver a clientes
        </a>

        <form method="POST"
              action="{{ $client->exists ? route('admin.clients.update', $client) : route('admin.clients.store') }}"
              class="panel mt-5 space-y-5 rounded-3xl p-6 sm:p-8">
            @csrf @if($client->exists) @method('PUT') @endif

            <div>
                <p class="text-[11px] font-extrabold uppercase tracking-[.18em] text-fuchsia-300">Datos de contacto</p>
                <h1 class="mt-1 text-2xl font-extrabold text-white">{{ $client->exists ? 'Actualiza su información' : 'Agrega un cliente a tu cartera' }}</h1>
            </div>

            <label class="block text-xs font-bold text-zinc-300">
                Nombre o negocio
                <input class="field mt-2" name="name" value="{{ old('name', $client->name) }}" maxlength="160" required>
            </label>

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="text-xs font-bold text-zinc-300">
                    WhatsApp
                    <input class="field font-mono-num mt-2" name="phone" type="tel" value="{{ old('phone', $client->phone) }}" maxlength="32" required>
                </label>
                <label class="text-xs font-bold text-zinc-300">
                    Correo (opcional)
                    <input class="field mt-2" name="email" type="email" value="{{ old('email', $client->email) }}">
                </label>
            </div>

            <label class="block text-xs font-bold text-zinc-300">
                Estado
                <select class="field mt-2" name="status">
                    <option value="active" @selected(old('status', $client->status ?: 'active') === 'active')>Activo</option>
                    <option value="follow-up" @selected(old('status', $client->status) === 'follow-up')>Seguimiento</option>
                    <option value="inactive" @selected(old('status', $client->status) === 'inactive')>Inactivo</option>
                </select>
            </label>

            <label class="block text-xs font-bold text-zinc-300">
                Notas
                <textarea class="field mt-2 min-h-24" name="notes" maxlength="2000">{{ old('notes', $client->notes) }}</textarea>
            </label>

            <div class="flex flex-wrap gap-3 pt-2">
                <button class="btn-primary text-xs">{{ $client->exists ? 'Guardar cambios' : 'Guardar cliente' }}</button>
                <a href="{{ route('admin.clients.index') }}" class="btn-subtle text-xs">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
