@extends('layouts.admin')
@section('title', $category->exists ? 'Editar categoría' : 'Nueva categoría')
@section('heading', $category->exists ? 'Editar categoría' : 'Nueva categoría')
@section('content')
    <div class="mx-auto max-w-2xl" data-cinema-enter>
        <a href="{{ route('admin.categories.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-zinc-400 transition hover:text-white">
            <x-icon name="arrow-left" class="size-4" />
            Volver a categorías
        </a>

        <form method="POST"
              action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
              class="panel mt-5 space-y-6 rounded-3xl p-6 sm:p-8">
            @csrf @if($category->exists) @method('PUT') @endif

            <div>
                <p class="text-[11px] font-extrabold uppercase tracking-[.18em] text-fuchsia-300">Organización del catálogo</p>
                <h1 class="mt-1 text-2xl font-extrabold text-white">
                    {{ $category->exists ? 'Actualizar categoría' : 'Crear nueva categoría' }}
                </h1>
                <p class="mt-1 text-xs text-zinc-400">
                    Las categorías permiten agrupar los productos en la tienda pública, facilitar las búsquedas y filtrar los reportes de inventario.
                </p>
            </div>

            @if($errors->any())
                <div class="rounded-2xl border border-rose-400/20 bg-rose-500/[.07] p-4 text-xs font-semibold text-rose-200">
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="space-y-4">
                <label class="block text-xs font-bold text-zinc-300">
                    Nombre de la categoría
                    <input class="field mt-2" name="name" value="{{ old('name', $category->name) }}"
                           placeholder="Ej. Bebidas, Lácteos, Limpieza..." maxlength="100" required autofocus>
                </label>

                <div>
                    <label class="block text-xs font-bold text-zinc-300 mb-2">Color distintivo (Badge)</label>
                    <div class="grid grid-cols-3 gap-3 sm:grid-cols-6">
                        @php
                            $colors = [
                                'fuchsia' => ['label' => 'Fucsia', 'bg' => 'bg-fuchsia-500/20 text-fuchsia-300 border-fuchsia-500/30'],
                                'violet'  => ['label' => 'Violeta', 'bg' => 'bg-violet-500/20 text-violet-300 border-violet-500/30'],
                                'emerald' => ['label' => 'Esmeralda', 'bg' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30'],
                                'amber'   => ['label' => 'Ámbar', 'bg' => 'bg-amber-500/20 text-amber-300 border-amber-500/30'],
                                'sky'     => ['label' => 'Cielo', 'bg' => 'bg-sky-500/20 text-sky-300 border-sky-500/30'],
                                'rose'    => ['label' => 'Rosa', 'bg' => 'bg-rose-500/20 text-rose-300 border-rose-500/30'],
                            ];
                            $selectedColor = old('color', $category->color ?: 'fuchsia');
                        @endphp
                        @foreach($colors as $val => $c)
                            <label class="relative flex cursor-pointer flex-col items-center gap-1.5 rounded-2xl border p-3 text-center transition hover:scale-105 {{ $c['bg'] }}">
                                <input type="radio" name="color" value="{{ $val }}" class="sr-only" @checked($selectedColor === $val)>
                                <span class="size-3.5 rounded-full {{ str_replace('/20', '', explode(' ', $c['bg'])[0]) }}"></span>
                                <span class="text-[11px] font-bold">{{ $c['label'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <label class="block text-xs font-bold text-zinc-300">
                    Descripción (opcional)
                    <textarea class="field mt-2 min-h-[90px] resize-y" name="description"
                              placeholder="Breve detalle de los tipos de productos que pertenecen a esta categoría..."
                              maxlength="500">{{ old('description', $category->description) }}</textarea>
                </label>
            </div>

            <label class="flex items-center gap-3 text-xs font-bold text-zinc-300">
                <input class="size-4 rounded accent-fuchsia-500" type="checkbox" name="active" value="1"
                       @checked(old('active', $category->exists ? $category->active : true))>
                Categoría activa para asignación de productos
            </label>

            <div class="flex flex-wrap items-center gap-3 pt-2">
                <button class="btn-primary text-xs">
                    {{ $category->exists ? 'Guardar cambios' : 'Crear categoría' }}
                </button>
                <a href="{{ route('admin.categories.index') }}" class="btn-subtle text-xs">Cancelar</a>
            </div>
        </form>
    </div>
@endsection

