<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Support\InputGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('q'));
        $escapedSearch = $search !== '' ? InputGuard::escapeLike($search) : '';
        $roleFilter = $request->integer('role_id');
        $statusFilter = (string) $request->string('status');
        $sort = (string) $request->string('sort');

        $users = User::query()
            ->with(['role' => fn ($q) => $q->withCount('permissions')])
            ->when($search !== '', function ($query) use ($escapedSearch): void {
                $query->where(function ($q) use ($escapedSearch): void {
                    $q->where('name', 'like', "%{$escapedSearch}%")
                        ->orWhere('email', 'like', "%{$escapedSearch}%")
                        ->orWhere('phone', 'like', "%{$escapedSearch}%")
                        ->orWhere('job_title', 'like', "%{$escapedSearch}%");
                });
            })
            ->when($roleFilter > 0, fn ($query) => $query->where('role_id', $roleFilter))
            ->when($statusFilter === 'active', fn ($query) => $query->where('active', true))
            ->when($statusFilter === 'inactive', fn ($query) => $query->where('active', false))
            ->when($sort === 'recent', fn ($query) => $query->latest('id'), fn ($query) => $query->orderBy('name'))
            ->get();

        $allRoles = Role::query()
            ->withCount(['users', 'permissions'])
            ->orderByDesc('is_super_admin')
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => User::query()->count(),
            'active' => User::query()->where('active', true)->count(),
            'inactive' => User::query()->where('active', false)->count(),
            'super_admins' => User::query()
                ->where('active', true)
                ->whereHas('role', fn ($q) => $q->where('is_super_admin', true))
                ->count(),
        ];

        return view('admin.users.index', compact(
            'users',
            'allRoles',
            'stats',
            'search',
            'roleFilter',
            'statusFilter',
            'sort'
        ));
    }

    public function create(): View
    {
        $roles = Role::query()
            ->with('permissions:id,key,module,name')
            ->orderByDesc('is_super_admin')
            ->orderBy('name')
            ->get();

        return view('admin.users.form', [
            'user' => new User(['active' => true, 'role_id' => Role::ensureDefaultAdminId()]),
            'roles' => $roles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'job_title' => $data['job_title'],
            'role_id' => $data['role_id'],
            'active' => $data['active'],
            'password' => $data['password'],
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Usuario «{$user->name}» creado y asignado correctamente.");
    }

    public function edit(User $user): View
    {
        $roles = Role::query()
            ->with('permissions:id,key,module,name')
            ->orderByDesc('is_super_admin')
            ->orderBy('name')
            ->get();

        return view('admin.users.form', compact('user', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);

        if ($error = $this->checkLockoutGuard($request->user(), $user, (int) $data['role_id'], (bool) $data['active'])) {
            return back()->withInput()->withErrors(['role_id' => $error]);
        }

        $attributes = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'job_title' => $data['job_title'],
            'role_id' => $data['role_id'],
            'active' => $data['active'],
        ];

        if (! empty($data['password'])) {
            $attributes['password'] = $data['password'];
        }

        $user->update($attributes);

        if (! $data['active']) {
            $this->terminateSessions($user->id);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Cuenta de «{$user->name}» actualizada.");
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $nextActive = ! $user->isActive();

        if ($error = $this->checkLockoutGuard($request->user(), $user, (int) $user->role_id, $nextActive)) {
            return back()->withErrors(['user' => $error]);
        }

        $user->update(['active' => $nextActive]);

        if (! $nextActive) {
            $this->terminateSessions($user->id);
        }

        $verb = $nextActive ? 'activada' : 'suspendida';

        return back()->with('success', "La cuenta de «{$user->name}» quedó {$verb}.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->id === $user->id) {
            return back()->withErrors(['user' => 'No puedes eliminar tu propia cuenta mientras tienes sesión iniciada.']);
        }

        if ($user->isSuperAdmin() && $user->isActive() && $this->activeSuperAdminCount() <= 1) {
            return back()->withErrors(['user' => 'Debe permanecer al menos un Super Administrador activo en la bodega.']);
        }

        $name = $user->name;
        $this->terminateSessions($user->id);
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Usuario «{$name}» eliminado.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?User $existing = null): array
    {
        $passwordRules = $existing
            ? ['nullable', 'confirmed', PasswordRule::min(12)->letters()->mixedCase()->numbers()]
            : ['required', 'confirmed', PasswordRule::min(12)->letters()->mixedCase()->numbers()];

        $validated = $request->validate(
            [
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($existing?->id)],
                'phone' => ['nullable', 'string', 'max:32'],
                'job_title' => ['nullable', 'string', 'max:80'],
                'role_id' => ['required', 'integer', 'exists:roles,id'],
                'active' => ['nullable', 'boolean'],
                'password' => $passwordRules,
            ],
            [
                'email.unique' => 'Ya existe otra cuenta registrada con ese correo.',
                'role_id.required' => 'Debes seleccionar un rol RBAC para esta cuenta.',
                'password.min' => 'La contraseña debe tener al menos 12 caracteres, mayúsculas, minúsculas y números.',
            ]
        );

        $nameNormalized = InputGuard::normalizePersonName($validated['name'], 'name');
        $phoneNormalized = InputGuard::normalizePeruPhone($validated['phone'] ?? null, required: false, field: 'phone');

        return [
            'name' => $nameNormalized,
            'email' => mb_strtolower(trim((string) $validated['email'])),
            'phone' => $phoneNormalized,
            'job_title' => filled($validated['job_title'] ?? null) ? InputGuard::cleanText((string) $validated['job_title']) : null,
            'role_id' => (int) $validated['role_id'],
            'active' => (bool) ($validated['active'] ?? false),
            'password' => $validated['password'] ?? null,
        ];
    }

    private function checkLockoutGuard(?User $actor, User $target, int $newRoleId, bool $newActive): ?string
    {
        if ($actor && $actor->id === $target->id) {
            if (! $newActive) {
                return 'No puedes suspender tu propia cuenta mientras tienes sesión activa.';
            }
            $newRole = Role::query()->with('permissions:id,key')->find($newRoleId);
            if ($newRole && ! $newRole->is_super_admin) {
                $keys = $newRole->permissions->pluck('key')->all();
                if (! in_array('users.manage', $keys, true) && ! in_array('roles.manage', $keys, true)) {
                    return 'No puedes asignarte un rol que te quite acceso para administrar usuarios y roles.';
                }
            }
        }

        if ($target->isSuperAdmin() && $target->isActive()) {
            $nextRole = Role::query()->find($newRoleId);
            $losesSuperAdmin = ! $newActive || ! ($nextRole?->is_super_admin ?? false);
            if ($losesSuperAdmin && $this->activeSuperAdminCount() <= 1) {
                return 'Debe permanecer al menos un Super Administrador activo en el sistema.';
            }
        }

        return null;
    }

    private function activeSuperAdminCount(): int
    {
        return User::query()
            ->where('active', true)
            ->whereHas('role', fn ($q) => $q->where('is_super_admin', true))
            ->count();
    }

    private function terminateSessions(int $userId): void
    {
        if (config('session.driver') === 'database') {
            DB::table('sessions')->where('user_id', $userId)->delete();
        }
    }
}
