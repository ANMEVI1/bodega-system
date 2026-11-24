<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;

#[Fillable(['name', 'email', 'password', 'role_id', 'phone', 'job_title', 'active', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** @var array<int, string>|null */
    protected ?array $cachedPermissionKeys = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function isActive(): bool
    {
        return (bool) ($this->active ?? true);
    }

    public function isSuperAdmin(): bool
    {
        if (! Schema::hasTable('roles')) {
            return true;
        }

        return (bool) $this->role?->is_super_admin;
    }

    /**
     * @return array<int, string>
     */
    public function permissionKeys(): array
    {
        if ($this->cachedPermissionKeys !== null) {
            return $this->cachedPermissionKeys;
        }

        if ($this->isSuperAdmin()) {
            return $this->cachedPermissionKeys = array_keys(Permission::CATALOG);
        }

        if (! $this->role_id || ! Schema::hasTable('permissions')) {
            return $this->cachedPermissionKeys = [];
        }

        $role = $this->relationLoaded('role')
            ? $this->role
            : $this->role()->with('permissions:id,key')->first();

        if (! $role) {
            return $this->cachedPermissionKeys = [];
        }

        if (! $role->relationLoaded('permissions')) {
            $role->load('permissions:id,key');
        }

        return $this->cachedPermissionKeys = $role->permissions->pluck('key')->values()->all();
    }

    public function flushPermissionCache(): void
    {
        $this->cachedPermissionKeys = null;
        $this->unsetRelation('role');
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($permission, $this->permissionKeys(), true);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        $owned = $this->permissionKeys();
        foreach ($permissions as $permission) {
            if (in_array(trim($permission), $owned, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve the first accessible admin route for users with scoped roles.
     */
    public function firstAllowedAdminRoute(): string
    {
        $candidates = [
            'admin.dashboard' => ['dashboard.view'],
            'admin.sales.index' => ['sales.view', 'sales.create'],
            'admin.products.index' => ['products.view', 'products.manage'],
            'admin.clients.index' => ['clients.view', 'clients.manage'],
            'admin.inventory.index' => ['inventory.view', 'products.manage'],
            'admin.suppliers.index' => ['suppliers.view', 'suppliers.manage'],
            'admin.contacts.index' => ['contacts.view', 'contacts.manage'],
            'admin.site.edit' => ['site.edit'],
            'admin.users.index' => ['users.view', 'users.manage'],
            'admin.roles.index' => ['roles.manage'],
        ];

        foreach ($candidates as $route => $required) {
            if ($this->hasAnyPermission($required)) {
                return $route;
            }
        }

        return 'admin.dashboard';
    }
}
