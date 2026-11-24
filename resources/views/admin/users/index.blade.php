@extends('layouts.admin')
@section('title', 'Usuarios y equipo')
@section('heading', 'Control de usuarios')
@section('content')
    @php
        $canManageUsers = auth()->user()?->hasPermission('users.manage') ?? false;
        $canManageRoles = auth()->user()?->hasPermission('roles.manage') ?? false;
    @endphp

    <div data-cinema-enter class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.18em] text-fuchsia-300/90">Seguridad y RBAC</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-white">
                Usuarios y accesos <span class="font-mono-num text-fuchsia-300">({{ $stats['total'] }})</span>
            </h1>
            <p class="mt-1.5 text-xs text-zinc-400">Administra cuentas del equipo, asigna roles granulares y controla sesiones activas.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            @if($canManageRoles)
                <a href="{{ route('admin.roles.index') }}" class="btn-subtle text-xs">
                    <x-icon name="shield" class="size-4 text-fuchsia-300" />
                    <span>Roles y matriz RBAC</span>
                </a>
            @endif
            @if($canManageUsers)
                <a href="{{ route('admin.users.create') }}" class="btn-primary text-xs">
                    <x-icon name="plus" class="size-4" />
                    <span>Nuevo usuario</span>
                </a>
            @endif
        </div>
    </div>

    <div data-cinema-enter style="--enter-delay: 1" class="mt-6 grid gap-3.5 sm:grid-cols-2 xl:grid-cols-4">
        <div class="panel spotlight-card rounded-3xl p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Total cuentas</p>
                <span class="grid size-8 place-items-center rounded-xl bg-white/[.05] text-zinc-300 ring-1 ring-white/10">
                    <x-icon name="users" class="size-4" />
                </span>
            </div>
            <p class="font-mono-num mt-2.5 text-2xl font-black text-white">{{ $stats['total'] }}</p>
            <p class="mt-1 text-[11px] text-zinc-500">Personal registrado en el sistema</p>
        </div>

        <div class="panel spotlight-card rounded-3xl p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Cuentas activas</p>
                <span class="grid size-8 place-items-center rounded-xl bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-400/25">
                    <x-icon name="check-circle" class="size-4" />
                </span>
            </div>
            <p class="font-mono-num mt-2.5 text-2xl font-black text-emerald-300">{{ $stats['active'] }}</p>
            <p class="mt-1 text-[11px] text-zinc-500">Habilitadas para operar en /admin</p>
        </div>

        <div class="panel spotlight-card rounded-3xl p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Suspendidas</p>
                <span class="grid size-8 place-items-center rounded-xl bg-rose-500/12 text-rose-300 ring-1 ring-rose-400/25">
                    <x-icon name="alert-triangle" class="size-4" />
                </span>
            </div>
            <p class="font-mono-num mt-2.5 text-2xl font-black {{ $stats['inactive'] > 0 ? 'text-rose-300' : 'text-zinc-300' }}">{{ $stats['inactive'] }}</p>
            <p class="mt-1 text-[11px] text-zinc-500">Acceso revocado en tiempo real</p>
        </div>

        <div class="panel spotlight-card rounded-3xl p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Super Admins</p>
                <span class="grid size-8 place-items-center rounded-xl bg-fuchsia-500/15 text-fuchsia-300 ring-1 ring-fuchsia-400/25">
                    <x-icon name="shield" class="size-4" />
                </span>
            </div>
            <p class="font-mono-num mt-2.5 text-2xl font-black text-fuchsia-300">{{ $stats['super_admins'] }}</p>
            <p class="mt-1 text-[11px] text-zinc-500">Control maestro y protección anti-bloqueo</p>
        </div>
    </div>

    <div data-cinema-enter style="--enter-delay: 2" class="mt-6">
        <form method="GET" class="panel flex flex-wrap items-center gap-2.5 rounded-3xl p-3.5 sm:p-4">
            <div class="relative min-w-[220px] flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-zinc-500" />
                <input class="field pl-10 text-xs" name="q" value="{{ $search }}" placeholder="Buscar por nombre, correo, cargo o celular...">
            </div>

            <select class="field w-full text-xs sm:w-48" name="role_id" aria-label="Filtrar por rol">
                <option value="">Todos los roles</option>
                @foreach($allRoles as $role)
                    <option value="{{ $role->id }}" @selected($roleFilter === $role->id)>{{ $role->name }} ({{ $role->users_count }})</option>
                @endforeach
            </select>

            <select class="field w-full text-xs sm:w-36" name="status" aria-label="Filtrar por estado">
                <option value="">Todos los estados</option>
                <option value="active" @selected($statusFilter === 'active')>Solo activos</option>
                <option value="inactive" @selected($statusFilter === 'inactive')>Suspendidos</option>
            </select>

            <select class="field w-full text-xs sm:w-40" name="sort" aria-label="Ordenar usuarios">
                <option value="name" @selected($sort !== 'recent')>Orden alfabético</option>
                <option value="recent" @selected($sort === 'recent')>Más recientes</option>
            </select>

            <button type="submit" class="btn-subtle text-xs">Filtrar</button>

            @if($search !== '' || $roleFilter > 0 || $statusFilter !== '' || $sort === 'recent')
                <a href="{{ route('admin.users.index') }}" class="text-xs font-bold text-zinc-400 transition hover:text-white">Limpiar</a>
            @endif
        </form>
    </div>

    <section data-reveal class="panel mt-5 overflow-hidden rounded-3xl">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[840px] text-left text-xs">
                <thead class="bg-white/[.03] text-[10px] font-extrabold uppercase tracking-wider text-zinc-500">
                    <tr>
                        <th class="px-6 py-4">Usuario y cargo</th>
                        <th class="px-4 py-4">Contacto</th>
                        <th class="px-4 py-4">Rol RBAC</th>
                        <th class="px-4 py-4">Estado</th>
                        <th class="px-4 py-4">Último ingreso</th>
                        @if($canManageUsers)
                            <th class="px-6 py-4 text-right">Acciones</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        @php
                            $isSelf = auth()->id() === $user->id;
                            $isLastAdmin = $user->isSuperAdmin() && $user->isActive() && $stats['super_admins'] <= 1;
                            $role = $user->role;
                            $badgeClass = $role?->color_badge_class ?? 'border-zinc-400/25 bg-zinc-500/12 text-zinc-300';
                        @endphp
                        <tr class="table-row">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-linear-to-br from-fuchsia-500/20 to-violet-600/25 text-xs font-black text-fuchsia-200 ring-1 ring-fuchsia-400/25">
                                        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <p class="truncate font-bold text-white">{{ $user->name }}</p>
                                            @if($isSelf)
                                                <span class="rounded-full border border-fuchsia-400/25 bg-fuchsia-500/15 px-2 py-0.5 text-[10px] font-extrabold text-fuchsia-200">Tu cuenta</span>
                                            @endif
                                        </div>
                                        <p class="mt-0.5 truncate text-[11px] text-zinc-500">{{ $user->job_title ?: 'Personal operativo' }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-4">
                                <p class="font-medium text-zinc-200">{{ $user->email }}</p>
                                <p class="font-mono-num mt-0.5 text-[11px] text-zinc-500">
                                    {{ $user->phone ? '+51 ' . $user->phone : 'Sin teléfono' }}
                                </p>
                            </td>

                            <td class="px-4 py-4">
                                @if($role)
                                    <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-bold {{ $badgeClass }}">
                                        <x-icon name="shield" class="size-3" />
                                        {{ $role->name }}
                                    </span>
                                    <p class="mt-1 text-[10px] text-zinc-500">
                                        {{ $role->is_super_admin ? 'Todos los permisos' : ($role->permissions_count ?? 0) . ' permisos activos' }}
                                    </p>
                                @else
                                    <span class="text-xs text-zinc-500">Sin rol asignado</span>
                                @endif
                            </td>

                            <td class="px-4 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold {{ $user->isActive() ? 'bg-emerald-400/10 text-emerald-200 ring-1 ring-emerald-400/25' : 'bg-rose-500/10 text-rose-200 ring-1 ring-rose-400/25' }}">
                                    <span class="size-1.5 rounded-full {{ $user->isActive() ? 'bg-emerald-400' : 'bg-rose-400' }}"></span>
                                    {{ $user->isActive() ? 'Activo' : 'Suspendido' }}
                                </span>
                            </td>

                            <td class="px-4 py-4 text-[11px] text-zinc-400">
                                @if($user->last_login_at)
                                    <p class="font-medium text-zinc-200">{{ $user->last_login_at->diffForHumans() }}</p>
                                    <p class="font-mono-num text-[10px] text-zinc-500">{{ $user->last_login_at->format('d/m/Y H:i') }}</p>
                                @else
                                    <span class="text-zinc-500">Pendiente</span>
                                @endif
                            </td>

                            @if($canManageUsers)
                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="btn-subtle px-3 py-1.5 text-[11px]">
                                            <x-icon name="edit" class="size-3.5" />
                                            <span>Editar</span>
                                        </a>

                                        @if(!$isSelf && !$isLastAdmin)
                                            <form method="POST" action="{{ route('admin.users.toggle', $user) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                        class="inline-flex items-center gap-1 rounded-xl border px-3 py-1.5 text-[11px] font-bold transition {{ $user->isActive() ? 'border-amber-400/20 bg-amber-500/10 text-amber-200 hover:bg-amber-500/20' : 'border-emerald-400/20 bg-emerald-500/10 text-emerald-200 hover:bg-emerald-500/20' }}">
                                                    {{ $user->isActive() ? 'Suspender' : 'Activar' }}
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                                  data-confirm="¿Eliminar permanentemente la cuenta de «{{ $user->name }}»? Sus sesiones activas se cerrarán de inmediato.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center gap-1 rounded-xl border border-rose-400/20 bg-rose-500/[.08] px-3 py-1.5 text-[11px] font-bold text-rose-200 transition hover:bg-rose-500/20">
                                                    <x-icon name="trash" class="size-3.5" />
                                                    <span>Eliminar</span>
                                                </button>
                                            </form>
                                        @else
                                            <span title="{{ $isSelf ? 'Cuenta en sesión activa' : 'Último Super Administrador activo protegido' }}"
                                                  class="inline-flex items-center gap-1 rounded-xl border border-white/10 bg-white/[.03] px-2.5 py-1.5 text-[10px] font-semibold text-zinc-500">
                                                Protegida
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManageUsers ? 6 : 5 }}" class="px-6 py-14 text-center">
                                <p class="text-sm font-bold text-zinc-200">No se encontraron usuarios con esos filtros.</p>
                                <p class="mt-1 text-xs text-zinc-500">Prueba limpiar la búsqueda o crea una nueva cuenta de acceso.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
