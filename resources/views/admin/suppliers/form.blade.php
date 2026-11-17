@extends('layouts.admin')
@section('title', $supplier->exists ? 'Editar proveedor' : 'Agregar proveedor')
@section('heading', $supplier->exists ? 'Editar proveedor' : 'Nuevo proveedor')
@section('content')
    <div class="mx-auto max-w-2xl" data-cinema-enter>
        <a href="{{ route('admin.suppliers.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-zinc-400 transition hover:text-white">
            <x-icon name="arrow-left" class="size-4" />
            Volver a proveedores
        </a>

        <form method="POST"
              action="{{ $supplier->exists ? route('admin.suppliers.update', $supplier) : route('admin.suppliers.store') }}"
              class="panel mt-5 space-y-5 rounded-3xl p-6 sm:p-8">
            @csrf @if($supplier->exists) @method('PUT') @endif

            <div>
                <p class="text-[11px] font-extrabold uppercase tracking-[.18em] text-cyan-300">Red de abastecimiento</p>
                <h1 class="mt-1 text-2xl font-extrabold text-white">{{ $supplier->exists ? 'Actualiza los datos' : 'Agrega un proveedor' }}</h1>
            </div>

            <label class="block text-xs font-bold text-zinc-300">
                Nombre del proveedor
                <input class="field mt-2" name="name" value="{{ old('name', $supplier->name) }}" maxlength="160" required>
            </label>

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="text-xs font-bold text-zinc-300">
                    Persona de contacto
                    <input class="field mt-2" name="contact_name" value="{{ old('contact_name', $supplier->contact_name) }}" maxlength="120">
                </label>
                <label class="text-xs font-bold text-zinc-300">
                    Rubro / especialidad
                    <input class="field mt-2" name="specialty" value="{{ old('specialty', $supplier->specialty) }}" maxlength="160">
                </label>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="text-xs font-bold text-zinc-300">
                    WhatsApp
                    <input class="field font-mono-num mt-2" name="phone" type="tel" value="{{ old('phone', $supplier->phone) }}" maxlength="32" required>
                </label>
                <label class="text-xs font-bold text-zinc-300">
                    Correo
                    <input class="field mt-2" name="email" type="email" value="{{ old('email', $supplier->email) }}">
                </label>
            </div>

            <label class="block text-xs font-bold text-zinc-300">
                Notas
                <textarea class="field mt-2 min-h-24" name="notes" maxlength="2000">{{ old('notes', $supplier->notes) }}</textarea>
            </label>

            <div class="flex flex-wrap gap-3 pt-2">
                <button class="btn-primary text-xs">{{ $supplier->exists ? 'Guardar cambios' : 'Guardar proveedor' }}</button>
                <a href="{{ route('admin.suppliers.index') }}" class="btn-subtle text-xs">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
