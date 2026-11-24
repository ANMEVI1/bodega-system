@extends('layouts.admin')
@section('title', $role->exists ? 'Editar rol RBAC' : 'Nuevo rol RBAC')
@section('heading', $role->exists ? 'Configurar matriz del rol' : 'Crear nuevo rol RBAC')
@section('content')
    @php
        $selectedColor = old('color', $role->color ?: 'violet');
        $selectedIds = collect(old('permissions', $selectedPermissions))->map(fn ($v) => (int) $v)->all();
        $totalPermsCount = $permissionsByModule->flatten(1)->count();
        $colorSwatches = [
            'fuchsia' => 'bg-fuchsia-500',
            'emerald' => 'bg-emerald-500',
            'amber' => 'bg-amber-400',
            'sky' => 'bg-sky-400',
            'violet' => 'bg-violet-500',
            'rose' => 'bg-rose-500',
        ];
    @endphp

    <div class="mx-auto max-w-5xl" data-cinema-enter>
        <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-zinc-400 transition hover:text-white">
            <x-icon name="arrow-left" class="size-4" />
            Volver a roles y permisos
        </a>

        <form method="POST"
              action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}"
              data-role-matrix-form
              class="panel mt-5 rounded-3xl p-5 sm:p-7">
            @csrf
            @if($role->exists)
                @method('PUT')
            @endif

            <header class="border-b border-white/[.07] pb-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-extrabold uppercase tracking-[.18em] text-fuchsia-300">Definición de perfil</p>
                        <h1 class="mt-1 text-2xl font-extrabold text-white">
                            {{ $role->exists ? 'Editar rol: ' . $role->name : 'Nuevo rol RBAC' }}
                        </h1>
                    </div>
                    @if($role->is_system)
                        <span class="rounded-full border border-fuchsia-400/25 bg-fuchsia-500/12 px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider text-fuchsia-200">
                            Rol base del sistema
                        </span>
                    @endif
                </div>
            </header>

            <section class="space-y-4 border-b border-white/[.07] py-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-xs font-bold text-zinc-300">
                        Nombre visible del rol
                        <input class="field mt-2" name="name" value="{{ old('name', $role->name) }}" maxlength="80" data-role-name-input placeholder="Ej. Supervisor Nocturno" required>
                        @error('name') <span class="mt-1 block text-[11px] font-medium text-rose-300">{{ $message }}</span> @enderror
                    </label>

                    <label class="block text-xs font-bold text-zinc-300">
                        Identificador único (slug)
                        <input class="field mt-2 font-mono-num {{ $role->is_system ? 'opacity-70 cursor-not-allowed' : '' }}"
                               name="slug"
                               value="{{ old('slug', $role->slug) }}"
                               maxlength="80"
                               data-role-slug-input
                               placeholder="supervisor-nocturno"
                               @if($role->is_system) readonly @endif
                               required>
                        @error('slug') <span class="mt-1 block text-[11px] font-medium text-rose-300">{{ $message }}</span> @enderror
                    </label>
                </div>

                <label class="block text-xs font-bold text-zinc-300">
                    Descripción operativa <span class="font-normal text-zinc-500">· opcional</span>
                    <input class="field mt-2" name="description" value="{{ old('description', $role->description) }}" maxlength="255" placeholder="Describe brevemente qué responsabilidades cubre este perfil">
                    @error('description') <span class="mt-1 block text-[11px] font-medium text-rose-300">{{ $message }}</span> @enderror
                </label>

                <div>
                    <span class="block text-xs font-bold text-zinc-300">Color distintivo en panel</span>
                    <div class="mt-2.5 flex flex-wrap gap-2.5">
                        @foreach($colors as $colorKey => $colorLabel)
                            <label class="cursor-pointer">
                                <input type="radio" name="color" value="{{ $colorKey }}" @checked($selectedColor === $colorKey) class="peer sr-only">
                                <span class="inline-flex items-center gap-2 rounded-2xl border border-white/10 bg-white/[.03] px-3 py-2 text-xs font-bold text-zinc-300 transition peer-checked:border-fuchsia-400/50 peer-checked:bg-fuchsia-500/15 peer-checked:text-white">
                                    <span class="size-2.5 rounded-full {{ $colorSwatches[$colorKey] ?? 'bg-fuchsia-500' }}"></span>
                                    {{ $colorLabel }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('color') <span class="mt-1 block text-[11px] font-medium text-rose-300">{{ $message }}</span> @enderror
                </div>
            </section>

            <section class="py-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-extrabold text-white">Matriz de permisos por módulo</h2>
                        <p class="mt-1 text-xs text-zinc-400">
                            <span data-perm-selected-count class="font-mono-num font-extrabold text-fuchsia-300">{{ count($selectedIds) }}</span>
                            de <span class="font-mono-num">{{ $totalPermsCount }}</span> permisos habilitados
                        </p>
                    </div>

                    @if(!$role->is_super_admin)
                        <div class="flex items-center gap-2">
                            <button type="button" data-perm-select-all class="btn-subtle px-3 py-1.5 text-[11px]">Marcar todos</button>
                            <button type="button" data-perm-select-none class="btn-subtle px-3 py-1.5 text-[11px]">Desmarcar</button>
                        </div>
                    @endif
                </div>

                @if($role->is_super_admin)
                    <div class="mt-4 rounded-2xl border border-fuchsia-400/25 bg-fuchsia-500/10 p-4 text-xs text-fuchsia-100">
                        <strong class="font-extrabold">Rol Super Administrador:</strong> posee acceso irrestricto a todos los módulos presentes y futuros por política del sistema. Puedes editar su nombre o color, pero sus permisos permanecen completos para evitar bloqueos accidentales.
                    </div>
                @endif

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    @foreach($permissionsByModule as $module => $perms)
                        <div data-perm-module-card class="rounded-3xl border border-white/[.08] bg-white/[.02] p-4 sm:p-5">
                            <div class="flex items-center justify-between gap-3 border-b border-white/[.07] pb-3">
                                <label class="flex cursor-pointer items-center gap-2.5">
                                    @if(!$role->is_super_admin)
                                        <input type="checkbox" data-module-toggle
                                               class="size-4 rounded border-white/25 bg-white/5 text-fuchsia-500 accent-fuchsia-500">
                                    @endif
                                    <span class="text-xs font-extrabold uppercase tracking-[.14em] text-white">{{ $module }}</span>
                                </label>
                                <span data-module-counter class="font-mono-num rounded-full border border-white/10 bg-white/[.04] px-2.5 py-0.5 text-[10px] font-bold text-zinc-300">
                                    0/{{ $perms->count() }}
                                </span>
                            </div>

                            <div class="mt-3 space-y-2.5">
                                @foreach($perms as $perm)
                                    @php
                                        $checked = $role->is_super_admin || in_array($perm->id, $selectedIds, true);
                                    @endphp
                                    <label class="flex items-start gap-3 rounded-2xl border border-white/[.06] bg-white/[.02] p-3 transition {{ $role->is_super_admin ? 'opacity-80' : 'cursor-pointer hover:border-fuchsia-400/25 hover:bg-white/[.04]' }}">
                                        <input type="checkbox"
                                               name="permissions[]"
                                               value="{{ $perm->id }}"
                                               data-perm-checkbox
                                               @checked($checked)
                                               @disabled($role->is_super_admin)
                                               class="mt-0.5 size-4 shrink-0 rounded border-white/25 bg-white/5 text-fuchsia-500 accent-fuchsia-500">
                                        <span class="min-w-0 flex-1">
                                            <span class="flex flex-wrap items-center justify-between gap-2">
                                                <span class="text-xs font-bold text-zinc-100">{{ $perm->name }}</span>
                                                <code class="font-mono-num rounded bg-black/40 px-1.5 py-0.5 text-[10px] text-fuchsia-300/90">{{ $perm->key }}</code>
                                            </span>
                                            <span class="mt-1 block text-[11px] leading-4 text-zinc-500">{{ $perm->description }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-white/[.07] pt-5">
                <a href="{{ route('admin.roles.index') }}" class="btn-subtle text-xs">Cancelar</a>
                <button type="submit" class="btn-primary text-xs">
                    <x-icon name="check-circle" class="size-4" />
                    <span>{{ $role->exists ? 'Guardar matriz del rol' : 'Crear rol RBAC' }}</span>
                </button>
            </div>
        </form>
    </div>
@endsection
