@extends('layouts.admin')
@section('title', $user->exists ? 'Editar usuario' : 'Nuevo usuario')
@section('heading', $user->exists ? 'Editar cuenta de usuario' : 'Crear usuario del equipo')
@section('content')
    @php
        $selectedRoleId = (int) old('role_id', $user->role_id);
        $isActive = (bool) old('active', $user->exists ? $user->isActive() : true);
    @endphp

    <div class="mx-auto max-w-5xl" data-cinema-enter>
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-zinc-400 transition hover:text-white">
            <x-icon name="arrow-left" class="size-4" />
            Volver a usuarios y accesos
        </a>

        <div class="mt-5 grid items-start gap-5 lg:grid-cols-[minmax(0,1.55fr)_minmax(280px,.85fr)]">
            <form method="POST"
                  action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}"
                  class="panel rounded-3xl p-5 sm:p-7">
                @csrf
                @if($user->exists)
                    @method('PUT')
                @endif

                <header class="border-b border-white/[.07] pb-5">
                    <p class="text-[11px] font-extrabold uppercase tracking-[.18em] text-fuchsia-300">Identidad y rol RBAC</p>
                    <h1 class="mt-1 text-2xl font-extrabold text-white">
                        {{ $user->exists ? 'Actualizar cuenta: ' . $user->name : 'Registrar integrante del equipo' }}
                    </h1>
                    <p class="mt-2 text-xs leading-5 text-zinc-400">
                        Define sus credenciales de ingreso, cargo interno y el rol RBAC que determinará qué módulos puede ver y operar.
                    </p>
                </header>

                <section class="space-y-4 border-b border-white/[.07] py-5">
                    <h2 class="text-sm font-extrabold text-white">Datos del colaborador</h2>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block text-xs font-bold text-zinc-300">
                            Nombre completo
                            <input class="field mt-2"
                                   name="name"
                                   value="{{ old('name', $user->name) }}"
                                   maxlength="120"
                                   pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñÀ-ÿ\s'\.\-]+"
                                   data-person-name="true"
                                   title="El nombre solo admite letras y espacios (no se permiten números)"
                                   autocomplete="name"
                                   placeholder="Ej. Valeria Paredes"
                                   required>
                            @error('name') <span class="mt-1 block text-[11px] font-medium text-rose-300">{{ $message }}</span> @enderror
                        </label>

                        <label class="block text-xs font-bold text-zinc-300">
                            Correo electrónico
                            <input class="field mt-2" type="email" name="email" value="{{ old('email', $user->email) }}" maxlength="255" placeholder="valeria@bodegalosolivos.pe" required>
                            @error('email') <span class="mt-1 block text-[11px] font-medium text-rose-300">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block text-xs font-bold text-zinc-300">
                            Cargo o puesto <span class="font-normal text-zinc-500">· opcional</span>
                            <input class="field mt-2" name="job_title" value="{{ old('job_title', $user->job_title) }}" maxlength="80" placeholder="Ej. Cajero turno tarde">
                            @error('job_title') <span class="mt-1 block text-[11px] font-medium text-rose-300">{{ $message }}</span> @enderror
                        </label>

                        <label class="block text-xs font-bold text-zinc-300">
                            Celular / WhatsApp <span class="font-normal text-zinc-500">· opcional</span>
                            @php
                                $userPhoneDigits = preg_replace('/\D+/', '', (string) $user->phone) ?? '';
                                if (strlen($userPhoneDigits) === 9 && str_starts_with($userPhoneDigits, '9')) {
                                    $userPhoneDigits = '51'.$userPhoneDigits;
                                }
                                $userPhoneVal = old('phone') !== null
                                    ? (string) old('phone')
                                    : ($userPhoneDigits !== '' ? '+'.$userPhoneDigits : '+51');
                            @endphp
                            <input class="field mt-2 font-mono-num"
                                   name="phone"
                                   type="tel"
                                   value="{{ $userPhoneVal }}"
                                   maxlength="12"
                                   pattern="(\+51|\+519[0-9]{8})?"
                                   inputmode="numeric"
                                   data-peru-phone="optional"
                                   title="Solo números de Perú: +51 seguido de 9 dígitos"
                                   autocomplete="tel"
                                   placeholder="+51987654321">
                            @error('phone') <span class="mt-1 block text-[11px] font-medium text-rose-300">{{ $message }}</span> @enderror
                        </label>
                    </div>
                </section>

                <section class="space-y-4 border-b border-white/[.07] py-5">
                    <div>
                        <h2 class="text-sm font-extrabold text-white">Asignación de rol y estado</h2>
                        <p class="mt-1 text-[11px] text-zinc-500">Los permisos se heredan automáticamente del rol seleccionado.</p>
                    </div>

                    <label class="block text-xs font-bold text-zinc-300">
                        Rol RBAC asignado
                        <select class="field mt-2" name="role_id" data-user-role-select required>
                            @foreach($roles as $role)
                                @php
                                    $roleMeta = [
                                        'name' => $role->name,
                                        'description' => $role->description,
                                        'is_super_admin' => $role->is_super_admin,
                                        'badge_class' => $role->color_badge_class,
                                        'permissions' => $role->permissions->pluck('name')->values()->all(),
                                    ];
                                @endphp
                                <option value="{{ $role->id }}"
                                        data-role-meta='@json($roleMeta)'
                                        @selected($selectedRoleId === $role->id)>
                                    {{ $role->name }} {{ $role->is_super_admin ? '(Control total)' : '(' . $role->permissions->count() . ' permisos)' }}
                                </option>
                            @endforeach
                        </select>
                        @error('role_id') <span class="mt-1 block text-[11px] font-medium text-rose-300">{{ $message }}</span> @enderror
                    </label>

                    <div class="rounded-2xl border border-white/[.08] bg-white/[.02] p-4">
                        <input type="hidden" name="active" value="0">
                        <label class="flex cursor-pointer items-center justify-between gap-4">
                            <div>
                                <p class="text-xs font-extrabold text-white">Cuenta habilitada para iniciar sesión</p>
                                <p class="mt-0.5 text-[11px] text-zinc-500">Si la desactivas, se bloquearán nuevos ingresos y se cerrarán sus sesiones activas.</p>
                            </div>
                            <input type="checkbox" name="active" value="1" @checked($isActive)
                                   class="size-5 rounded-lg border-white/20 bg-white/5 text-fuchsia-500 accent-fuchsia-500 focus:ring-fuchsia-500/40">
                        </label>
                    </div>
                </section>

                <section class="space-y-4 py-5">
                    <div>
                        <h2 class="text-sm font-extrabold text-white">
                            Contraseña de acceso
                            @if($user->exists)
                                <span class="font-normal text-zinc-400">· deja en blanco para mantener la actual</span>
                            @endif
                        </h2>
                        <p class="mt-1 text-[11px] text-zinc-500">Mínimo 12 caracteres combinando mayúsculas, minúsculas y números.</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div data-password-field>
                            <label class="block text-xs font-bold text-zinc-300">
                                {{ $user->exists ? 'Nueva contraseña' : 'Contraseña' }}
                            </label>
                            <div class="relative mt-2">
                                <input class="field pr-11"
                                       type="password"
                                       name="password"
                                       minlength="12"
                                       data-password-strength="user-password-strength"
                                       autocomplete="new-password"
                                       placeholder="Ej. NexoBodega2026!"
                                       @if(!$user->exists) required @endif>
                                <button type="button" data-toggle-password aria-label="Mostrar u ocultar contraseña"
                                        class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-zinc-400 transition hover:bg-white/[.06] hover:text-white">
                                    <span data-eye-open><x-icon name="eye" class="size-4" /></span>
                                    <span data-eye-closed class="hidden"><x-icon name="eye-off" class="size-4" /></span>
                                </button>
                            </div>
                            @error('password') <span class="mt-1 block text-[11px] font-medium text-rose-300">{{ $message }}</span> @enderror
                        </div>

                        <div data-password-field>
                            <label class="block text-xs font-bold text-zinc-300">Confirmar contraseña</label>
                            <div class="relative mt-2">
                                <input class="field pr-11"
                                       type="password"
                                       name="password_confirmation"
                                       minlength="12"
                                       autocomplete="new-password"
                                       placeholder="Repite la contraseña"
                                       @if(!$user->exists) required @endif>
                                <button type="button" data-toggle-password aria-label="Mostrar u ocultar confirmación"
                                        class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-zinc-400 transition hover:bg-white/[.06] hover:text-white">
                                    <span data-eye-open><x-icon name="eye" class="size-4" /></span>
                                    <span data-eye-closed class="hidden"><x-icon name="eye-off" class="size-4" /></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="user-password-strength" class="space-y-2 rounded-2xl border border-white/[.07] bg-white/[.02] p-3.5">
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-white/[.07]">
                            <div data-strength-bar class="h-full w-0 rounded-full bg-zinc-700 transition-all duration-300"></div>
                        </div>
                        <p data-strength-label class="text-[11px] text-zinc-500">Mínimo 12 caracteres, mayúsculas, minúsculas y números</p>
                        <div class="flex flex-wrap gap-1.5 text-[10px] font-semibold">
                            <span data-rule-len class="rounded-full border border-white/10 bg-white/[.02] px-2.5 py-0.5 text-zinc-500 transition">12+ caracteres</span>
                            <span data-rule-mixed class="rounded-full border border-white/10 bg-white/[.02] px-2.5 py-0.5 text-zinc-500 transition">Mayús. y minús.</span>
                            <span data-rule-num class="rounded-full border border-white/10 bg-white/[.02] px-2.5 py-0.5 text-zinc-500 transition">Al menos 1 número</span>
                        </div>
                    </div>
                </section>

                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-white/[.07] pt-5">
                    <a href="{{ route('admin.users.index') }}" class="btn-subtle text-xs">Cancelar</a>
                    <button type="submit" class="btn-primary text-xs">
                        <x-icon name="check-circle" class="size-4" />
                        <span>{{ $user->exists ? 'Guardar cambios' : 'Crear usuario' }}</span>
                    </button>
                </div>
            </form>

            <aside class="space-y-4">
                <section data-role-preview class="panel spotlight-card rounded-3xl p-5">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[10px] font-extrabold uppercase tracking-[.18em] text-zinc-500">Vista previa RBAC</span>
                        <span data-role-preview-badge class="inline-flex items-center rounded-full border border-fuchsia-400/25 bg-fuchsia-500/12 px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider text-fuchsia-300">
                            RBAC
                        </span>
                    </div>

                    <h3 data-role-preview-title class="mt-3 text-base font-extrabold text-white">Cargando rol...</h3>
                    <p data-role-preview-desc class="mt-1.5 text-xs leading-5 text-zinc-400">Selecciona un rol para previsualizar sus permisos efectivos.</p>

                    <div class="mt-4 flex items-center justify-between border-t border-white/[.07] pt-3">
                        <span class="text-[11px] font-bold text-zinc-400">Alcance de permisos</span>
                        <span data-role-preview-count class="font-mono-num text-[11px] font-extrabold text-fuchsia-300">—</span>
                    </div>

                    <div data-role-preview-perms class="mt-3 flex flex-wrap gap-1.5"></div>

                    @if(auth()->user()?->hasPermission('roles.manage'))
                        <a href="{{ route('admin.roles.index') }}" class="mt-4 inline-flex items-center gap-1.5 text-[11px] font-bold text-fuchsia-300 transition hover:text-fuchsia-200">
                            <x-icon name="shield" class="size-3.5" />
                            Personalizar matriz de roles
                        </a>
                    @endif
                </section>

                <section class="panel rounded-3xl p-5">
                    <p class="text-[11px] font-extrabold uppercase tracking-[.16em] text-zinc-500">Reglas de seguridad</p>
                    <ul class="mt-3 space-y-2.5 text-xs leading-5 text-zinc-400">
                        <li class="flex items-start gap-2">
                            <x-icon name="shield" class="mt-0.5 size-3.5 shrink-0 text-emerald-400" />
                            <span>El sistema impide suspender o degradar al último Super Administrador activo.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <x-icon name="check-circle" class="mt-0.5 size-3.5 shrink-0 text-fuchsia-400" />
                            <span>Cada cambio de rol o suspensión aplica inmediatamente en cada solicitud de ruta.</span>
                        </li>
                    </ul>
                </section>
            </aside>
        </div>
    </div>
@endsection
