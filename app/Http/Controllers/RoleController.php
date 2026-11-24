<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        Role::syncSystemCatalog();

        $roles = Role::query()
            ->with(['permissions:id,key,module,name'])
            ->withCount(['users', 'permissions'])
            ->orderByDesc('is_super_admin')
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get();

        $permissionsByModule = $this->groupedPermissions();
        $totalPermissions = Permission::query()->count();

        $stats = [
            'total_roles' => $roles->count(),
            'system_roles' => $roles->where('is_system', true)->count(),
            'custom_roles' => $roles->where('is_system', false)->count(),
            'total_permissions' => $totalPermissions,
        ];

        return view('admin.roles.index', compact('roles', 'permissionsByModule', 'stats', 'totalPermissions'));
    }

    public function create(Request $request): View
    {
        Role::syncSystemCatalog();

        $templateId = $request->integer('duplicate');
        $templateRole = $templateId > 0 ? Role::query()->with('permissions:id')->find($templateId) : null;

        $selectedPermissions = $templateRole
            ? $templateRole->permissions->pluck('id')->all()
            : Permission::query()->whereIn('key', ['dashboard.view'])->pluck('id')->all();

        $role = new Role([
            'name' => $templateRole ? $templateRole->name.' (Copia)' : '',
            'description' => $templateRole?->description ?? '',
            'color' => $templateRole?->color ?? 'violet',
        ]);

        return view('admin.roles.form', [
            'role' => $role,
            'permissionsByModule' => $this->groupedPermissions(),
            'selectedPermissions' => $selectedPermissions,
            'colors' => Role::COLORS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $role = Role::query()->create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'],
            'color' => $data['color'],
            'is_system' => false,
            'is_super_admin' => false,
        ]);

        $role->permissions()->sync($data['permissions']);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', "Rol RBAC «{$role->name}» creado con ".count($data['permissions']).' permisos.');
    }

    public function edit(Role $role): View
    {
        Role::syncSystemCatalog();
        $role->load('permissions:id');

        return view('admin.roles.form', [
            'role' => $role,
            'permissionsByModule' => $this->groupedPermissions(),
            'selectedPermissions' => $role->is_super_admin
                ? Permission::query()->pluck('id')->all()
                : $role->permissions->pluck('id')->all(),
            'colors' => Role::COLORS,
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $this->validated($request, $role);

        $role->update([
            'name' => $data['name'],
            'slug' => $role->is_system ? $role->slug : $data['slug'],
            'description' => $data['description'],
            'color' => $data['color'],
        ]);

        if ($role->is_super_admin) {
            $role->permissions()->sync(Permission::query()->pluck('id')->all());
        } else {
            $role->permissions()->sync($data['permissions']);
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('success', "Matriz de permisos de «{$role->name}» actualizada.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_system) {
            return back()->withErrors(['role' => 'Los roles base del sistema no se pueden eliminar (puedes personalizar sus permisos).']);
        }

        if ($role->users()->exists()) {
            return back()->withErrors(['role' => "Reasigna primero a los usuarios que tienen el rol «{$role->name}» antes de eliminarlo."]);
        }

        $name = $role->name;
        $role->permissions()->detach();
        $role->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', "Rol «{$name}» eliminado.");
    }

    /**
     * @return Collection<string, Collection<int, Permission>>
     */
    private function groupedPermissions(): Collection
    {
        $moduleOrder = collect(Permission::CATALOG)->pluck('module')->unique()->values()->all();

        return Permission::query()
            ->orderBy('id')
            ->get()
            ->groupBy('module')
            ->sortBy(function ($items, string $module) use ($moduleOrder): int {
                $idx = array_search($module, $moduleOrder, true);

                return $idx === false ? 999 : $idx;
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Role $existing = null): array
    {
        $rawSlug = trim((string) $request->input('slug', ''));
        $computedSlug = $existing?->is_system
            ? $existing->slug
            : Str::slug($rawSlug !== '' ? $rawSlug : (string) $request->input('name', ''));

        $request->merge(['slug' => $computedSlug]);

        $validated = $request->validate(
            [
                'name' => ['required', 'string', 'max:80'],
                'slug' => [
                    'required',
                    'string',
                    'max:80',
                    'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                    Rule::unique('roles', 'slug')->ignore($existing?->id),
                ],
                'description' => ['nullable', 'string', 'max:255'],
                'color' => ['required', Rule::in(array_keys(Role::COLORS))],
                'permissions' => ['nullable', 'array'],
                'permissions.*' => ['integer', 'exists:permissions,id'],
            ],
            [
                'slug.unique' => 'Ya existe otro rol con ese identificador (slug).',
                'slug.regex' => 'El identificador del rol solo puede contener minúsculas, números y guiones.',
            ]
        );

        return [
            'name' => trim((string) $validated['name']),
            'slug' => $computedSlug,
            'description' => filled($validated['description'] ?? null) ? trim((string) $validated['description']) : null,
            'color' => (string) $validated['color'],
            'permissions' => array_values(array_unique(array_map('intval', $validated['permissions'] ?? []))),
        ];
    }
}
