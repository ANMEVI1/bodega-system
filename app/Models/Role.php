<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Role extends Model
{
    public const COLORS = [
        'fuchsia' => 'Fucsia',
        'emerald' => 'Esmeralda',
        'amber' => 'Ámbar',
        'sky' => 'Cielo',
        'violet' => 'Violeta',
        'rose' => 'Coral',
    ];

    /**
     * Built-in starter RBAC role definitions seeded automatically.
     *
     * @var array<string, array{name: string, description: string, color: string, is_system: bool, is_super_admin: bool, permissions: array<int, string>}>
     */
    public const SYSTEM_ROLES = [
        'super-admin' => [
            'name' => 'Super Administrador',
            'description' => 'Control total de operaciones, configuración web, usuarios y matriz RBAC.',
            'color' => 'fuchsia',
            'is_system' => true,
            'is_super_admin' => true,
            'permissions' => ['*'],
        ],
        'cajero' => [
            'name' => 'Cajero / Ventas',
            'description' => 'Atiende caja, registra ventas, consulta catálogo/clientes y responde consultas web.',
            'color' => 'emerald',
            'is_system' => true,
            'is_super_admin' => false,
            'permissions' => [
                'dashboard.view',
                'clients.view',
                'clients.manage',
                'products.view',
                'sales.view',
                'sales.create',
                'contacts.view',
                'contacts.manage',
            ],
        ],
        'almacenero' => [
            'name' => 'Almacenero y Compras',
            'description' => 'Gestiona productos, categorías, alertas de inventario y cartera de proveedores.',
            'color' => 'amber',
            'is_system' => true,
            'is_super_admin' => false,
            'permissions' => [
                'dashboard.view',
                'products.view',
                'products.manage',
                'categories.manage',
                'inventory.view',
                'suppliers.view',
                'suppliers.manage',
                'exports.download',
            ],
        ],
        'editor-web' => [
            'name' => 'Editor Web y Atención',
            'description' => 'Administra textos públicos, WhatsApp, chatbot comercial y consultas de clientes.',
            'color' => 'violet',
            'is_system' => true,
            'is_super_admin' => false,
            'permissions' => [
                'dashboard.view',
                'products.view',
                'contacts.view',
                'contacts.manage',
                'site.edit',
            ],
        ],
        'consulta' => [
            'name' => 'Supervisor Lectura',
            'description' => 'Acceso de solo lectura sobre indicadores, catálogo, clientes, proveedores y ventas.',
            'color' => 'sky',
            'is_system' => true,
            'is_super_admin' => false,
            'permissions' => [
                'dashboard.view',
                'clients.view',
                'products.view',
                'inventory.view',
                'suppliers.view',
                'sales.view',
                'contacts.view',
            ],
        ],
    ];

    protected $fillable = ['name', 'slug', 'description', 'color', 'is_system', 'is_super_admin'];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_super_admin' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $role): void {
            if (empty($role->slug)) {
                $role->slug = Str::slug($role->name);
            }
        });
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getColorBadgeClassAttribute(): string
    {
        return match ($this->color) {
            'emerald' => 'border-emerald-400/25 bg-emerald-500/12 text-emerald-300',
            'amber' => 'border-amber-400/25 bg-amber-500/12 text-amber-300',
            'sky' => 'border-sky-400/25 bg-sky-500/12 text-sky-300',
            'violet' => 'border-violet-400/25 bg-violet-500/12 text-violet-300',
            'rose' => 'border-rose-400/25 bg-rose-500/12 text-rose-300',
            default => 'border-fuchsia-400/25 bg-fuchsia-500/12 text-fuchsia-300',
        };
    }

    /**
     * Ensure all RBAC permissions and starter roles exist idempotently.
     */
    public static function syncSystemCatalog(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles') || ! Schema::hasTable('permission_role')) {
            return;
        }

        $permissionIdsByKey = [];
        foreach (Permission::CATALOG as $key => $meta) {
            $perm = Permission::query()->updateOrCreate(
                ['key' => $key],
                [
                    'module' => $meta['module'],
                    'name' => $meta['name'],
                    'description' => $meta['description'],
                ]
            );
            $permissionIdsByKey[$key] = $perm->id;
        }

        $allPermissionIds = array_values($permissionIdsByKey);

        foreach (self::SYSTEM_ROLES as $slug => $config) {
            $role = self::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $config['name'],
                    'description' => $config['description'],
                    'color' => $config['color'],
                    'is_system' => $config['is_system'],
                    'is_super_admin' => $config['is_super_admin'],
                ]
            );

            if ($role->wasRecentlyCreated || $config['is_super_admin']) {
                $targetIds = $config['permissions'] === ['*']
                    ? $allPermissionIds
                    : collect($config['permissions'])->map(fn (string $k) => $permissionIdsByKey[$k] ?? null)->filter()->all();

                $role->permissions()->sync($targetIds);
            }
        }
    }

    public static function ensureDefaultAdminId(): ?int
    {
        if (! Schema::hasTable('roles')) {
            return null;
        }

        $existingId = self::query()->where('slug', 'super-admin')->value('id');
        if ($existingId) {
            return (int) $existingId;
        }

        self::syncSystemCatalog();

        return (int) self::query()->where('slug', 'super-admin')->value('id') ?: null;
    }
}
