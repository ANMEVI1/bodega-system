@extends('layouts.admin')
@section('title', 'Categorías')
@section('heading', 'Categorías de productos')
@section('content')
    <div data-cinema-enter class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.18em] text-fuchsia-300/90">Estructura del catálogo</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-white">
                Categorías <span class="font-mono-num text-fuchsia-300">({{ $categories->total() }})</span>
            </h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.products.index') }}" class="btn-subtle text-xs">
                <x-icon name="shopping-bag" class="size-4 text-zinc-400" />
                <span>Ver productos</span>
            </a>
            <a href="{{ route('admin.categories.create') }}" class="btn-primary text-xs">
                <x-icon name="plus" class="size-4" />
                <span>Nueva categoría</span>
            </a>
        </div>
    </div>

    <div data-cinema-enter style="--enter-delay: 1" class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <form class="flex flex-1 flex-wrap gap-2.5" method="GET">
            <div class="relative max-w-sm flex-1 min-w-[210px]">
                <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-zinc-500" />
                <input class="field pl-10 text-xs" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o descripción">
            </div>
            <select class="field max-w-44 text-xs" name="status">
                <option value="">Todos los estados</option>
                <option value="1" @selected(request('status') === '1')>Activas</option>
                <option value="0" @selected(request('status') === '0')>Inactivas</option>
            </select>
            <button class="btn-subtle text-xs">Filtrar</button>
            @if(request()->hasAny(['q', 'status']))
                <a href="{{ route('admin.categories.index') }}" class="btn-subtle text-xs text-zinc-400">Limpiar</a>
            @endif
        </form>
        <div class="flex items-center gap-2 text-xs text-zinc-400">
            <span class="inline-block size-2 rounded-full bg-emerald-400"></span>
            <span>{{ $activeCount }} activas de {{ $totalCategories }} en total</span>
        </div>
    </div>

    @if($errors->has('category'))
        <div data-cinema-enter class="mt-4 flex items-center gap-3 rounded-2xl border border-rose-400/20 bg-rose-500/[.07] px-4 py-3 text-xs font-semibold text-rose-200">
            <x-icon name="alert-triangle" class="size-4 shrink-0 text-rose-400" />
            <span>{{ $errors->first('category') }}</span>
        </div>
    @endif

    <section data-reveal class="panel mt-5 overflow-hidden rounded-3xl">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-xs">
                <thead class="bg-white/[.03] text-[10px] font-extrabold uppercase tracking-wider text-zinc-500">
                    <tr>
                        <th class="px-6 py-4">Categoría</th>
                        <th class="px-4 py-4">Descripción</th>
                        <th class="px-4 py-4">Productos</th>
                        <th class="px-4 py-4">Estado</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr class="table-row">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-xs font-bold {{ $category->color_badge_class }}">
                                        <x-icon name="tag" class="size-3" />
                                        {{ $category->name }}
                                    </span>
                                    <span class="font-mono-num text-[11px] text-zinc-500">/{{ $category->slug }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-zinc-400">
                                {{ $category->description ?: 'Sin descripción' }}
                            </td>
                            <td class="px-4 py-4">
                                @if($category->products_count > 0)
                                    <a href="{{ route('admin.products.index', ['category' => $category->name]) }}"
                                       class="font-mono-num inline-flex items-center gap-1 rounded-md bg-white/[.05] px-2 py-0.5 text-xs font-bold text-fuchsia-300 transition hover:bg-white/[.1]">
                                        <span>{{ $category->products_count }}</span>
                                        <span class="text-[10px] text-zinc-400">{{ $category->products_count === 1 ? 'producto' : 'productos' }}</span>
                                        <x-icon name="arrow-up-right" class="size-3 text-zinc-500" />
                                    </a>
                                @else
                                    <span class="font-mono-num text-xs text-zinc-600">0 productos</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold {{ $category->active ? 'bg-emerald-400/10 text-emerald-200 ring-1 ring-emerald-400/20' : 'bg-white/[.05] text-zinc-400' }}">
                                    <span class="size-1.5 rounded-full {{ $category->active ? 'bg-emerald-400' : 'bg-zinc-500' }}"></span>
                                    {{ $category->active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.categories.edit', $category) }}" class="btn-subtle px-3 py-1.5 text-[11px]">
                                        <x-icon name="edit" class="size-3.5" />
                                        Editar
                                    </a>
                                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                                          data-confirm="¿Seguro que deseas eliminar la categoría «{{ $category->name }}»?">
                                        @csrf @method('DELETE')
                                        <button class="inline-flex items-center gap-1 rounded-xl border border-rose-400/15 bg-rose-500/[.06] px-3 py-1.5 text-[11px] font-bold text-rose-200 transition hover:bg-rose-500/15">
                                            <x-icon name="trash" class="size-3.5" />
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-14 text-center text-zinc-500">
                                No se encontraron categorías registradas. Comienza creando una categoría para organizar tus productos.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-white/[.06] px-6 py-4">{{ $categories->links() }}</div>
    </section>
@endsection

