@extends('layouts.admin')
@section('title', 'Roles y permisos RBAC')
@section('heading', 'Roles y matriz de permisos')
@section('content')
    <div data-cinema-enter class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.18em] text-fuchsia-300/90">Seguridad granular (RBAC)</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-white">
                Roles y permisos <span class="font-mono-num text-fuchsia-300">({{ $stats['total_roles'] }})</span>
            </h1>
            <p class="mt-1.5 text-xs text-zinc-400">Diseña perfiles a medida para cajeros, almaceneros, editores web o supervisores de tu bodega.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            @if(auth()->user()?->hasAnyPermission(['users.view', 'users.manage']))
                <a href="{{ route('admin.users.index') }}" class="btn-subtle text-xs">
                    <x-icon name="users" class="size-4 text-zinc-300" />
                    <span>Ver usuarios</span>
                </a>
            @endif
            <a href="{{ route('admin.roles.create') }}" class="btn-primary text-xs">
                <x-icon name="plus" class="size-4" />
                <span>Nuevo rol RBAC</span>
            </a>
        </div>
    </div>

    <div data-cinema-enter style="--enter-delay: 1" class="mt-6 grid gap-3.5 sm:grid-cols-2 xl:grid-cols-4">
        <div class="panel spotlight-card rounded-3xl p-4 sm:p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Total de roles</p>
            <p class="font-mono-num mt-2 text-2xl font-black text-white">{{ $stats['total_roles'] }}</p>
            <p class="mt-1 text-[11px] text-zinc-500">Perfiles activos en la organización</p>
        </div>

        <div class="panel spotlight-card rounded-3xl p-4 sm:p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Plantillas base</p>
            <p class="font-mono-num mt-2 text-2xl font-black text-fuchsia-300">{{ $stats['system_roles'] }}</p>
            <p class="mt-1 text-[11px] text-zinc-500">Roles protegidos del sistema</p>
        </div>

        <div class="panel spotlight-card rounded-3xl p-4 sm:p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Roles personalizados</p>
            <p class="font-mono-num mt-2 text-2xl font-black text-emerald-300">{{ $stats['custom_roles'] }}</p>
            <p class="mt-1 text-[11px] text-zinc-500">Creados por el administrador</p>
        </div>

        <div class="panel spotlight-card rounded-3xl p-4 sm:p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Permisos granulares</p>
            <p class="font-mono-num mt-2 text-2xl font-black text-amber-300">{{ $stats['total_permissions'] }}</p>
            <p class="mt-1 text-[11px] text-zinc-500">Distribuidos en {{ $permissionsByModule->count() }} módulos</p>
        </div>
    </div>

    <section data-cinema-enter style="--enter-delay: 2" class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach($roles as $role)
            @php
                $permCount = $role->is_super_admin ? $totalPermissions : $role->permissions_count;
                $coveragePct = $totalPermissions > 0 ? (int) round(($permCount / $totalPermissions) * 100) : 0;
            @endphp
            <article class="panel spotlight-card flex flex-col justify-between rounded-3xl p-5">
                <div>
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider {{ $role->color_badge_class }}">
                                <x-icon name="shield" class="size-3" />
                                {{ $role->is_super_admin ? 'Maestro' : ($role->is_system ? 'Sistema' : 'Personalizado') }}
                            </span>
                            <h2 class="mt-2.5 truncate text-base font-extrabold text-white">{{ $role->name }}</h2>
                            <p class="font-mono-num text-[11px] text-zinc-500">{{ $role->slug }}</p>
                        </div>

                        <div class="text-right">
                            <p class="font-mono-num text-lg font-black text-white">{{ $role->users_count }}</p>
                            <p class="text-[10px] font-semibold text-zinc-500">{{ $role->users_count === 1 ? 'usuario' : 'usuarios' }}</p>
                        </div>
                    </div>

                    <p class="mt-3 text-xs leading-5 text-zinc-400">
                        {{ $role->description ?: 'Rol operativo personalizado para asignación de accesos.' }}
                    </p>

                    <div class="mt-4">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="font-bold text-zinc-400">Cobertura RBAC</span>
                            <span class="font-mono-num font-extrabold text-zinc-200">{{ $permCount }}/{{ $totalPermissions }} ({{ $coveragePct }}%)</span>
                        </div>
                        <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-white/[.06]">
                            <div class="h-full rounded-full bg-linear-to-r from-fuchsia-500 to-violet-500" style="width: {{ $coveragePct }}%"></div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-white/[.07] pt-4">
                    <a href="{{ route('admin.roles.edit', $role) }}" class="btn-subtle flex-1 justify-center px-3 py-1.5 text-[11px]">
                        <x-icon name="edit" class="size-3.5" />
                        <span>{{ $role->is_super_admin ? 'Ver ficha' : 'Editar matriz' }}</span>
                    </a>

                    <a href="{{ route('admin.roles.create', ['duplicate' => $role->id]) }}"
                       title="Crear nuevo rol tomando este como plantilla"
                       class="btn-subtle px-3 py-1.5 text-[11px]">
                        Duplicar
                    </a>

                    @if(!$role->is_system && $role->users_count === 0)
                        <form method="POST" action="{{ route('admin.roles.destroy', $role) }}"
                              data-confirm="¿Eliminar el rol RBAC «{{ $role->name }}»? Esta acción es irreversible.">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center gap-1 rounded-xl border border-rose-400/20 bg-rose-500/[.08] px-3 py-1.5 text-[11px] font-bold text-rose-200 transition hover:bg-rose-500/20">
                                <x-icon name="trash" class="size-3.5" />
                            </button>
                        </form>
                    @endif
                </div>
            </article>
        @endforeach
    </section>

    <section data-reveal class="panel mt-6 overflow-hidden rounded-3xl">
        <div class="border-b border-white/[.07] px-6 py-5">
            <p class="text-[10px] font-extrabold uppercase tracking-[.2em] text-fuchsia-300">Auditoría comparativa</p>
            <h2 class="mt-1 text-lg font-extrabold text-white">Matriz completa de permisos por rol</h2>
            <p class="mt-1 text-xs text-zinc-400">Verifica de un vistazo qué operaciones tiene autorizadas cada perfil.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-xs">
                <thead class="bg-white/[.03] text-[10px] font-extrabold uppercase tracking-wider text-zinc-500">
                    <tr>
                        <th class="px-6 py-4">Módulo y permiso</th>
                        @foreach($roles as $role)
                            <th class="px-3 py-4 text-center">
                                <span class="inline-block max-w-[120px] truncate text-zinc-200">{{ $role->name }}</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($permissionsByModule as $module => $perms)
                        <tr class="bg-white/[.02]">
                            <td colspan="{{ 1 + $roles->count() }}" class="px-6 py-2 text-[10px] font-extrabold uppercase tracking-[.18em] text-fuchsia-300/90">
                                {{ $module }}
                            </td>
                        </tr>
                        @foreach($perms as $perm)
                            <tr class="table-row">
                                <td class="px-6 py-3">
                                    <p class="font-bold text-zinc-200">{{ $perm->name }}</p>
                                    <p class="font-mono-num text-[10px] text-zinc-500">{{ $perm->key }}</p>
                                </td>
                                @foreach($roles as $role)
                                    @php
                                        $granted = $role->is_super_admin || $role->permissions->contains('id', $perm->id);
                                    @endphp
                                    <td class="px-3 py-3 text-center">
                                        @if($granted)
                                            <span class="inline-grid size-6 place-items-center rounded-full bg-emerald-400/15 text-emerald-300 ring-1 ring-emerald-400/30" title="Permitido">
                                                <x-icon name="check-circle" class="size-3.5" />
                                            </span>
                                        @else
                                            <span class="text-zinc-700" title="Sin acceso">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
