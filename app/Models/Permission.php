<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    /**
     * Granular RBAC permissions grouped by functional module.
     *
     * @var array<string, array{module: string, name: string, description: string}>
     */
    public const CATALOG = [
        'dashboard.view' => [
            'module' => 'Inicio',
            'name' => 'Ver panel e indicadores',
            'description' => 'Acceder al resumen general de ventas, stock crítico y consultas.',
        ],
        'clients.view' => [
            'module' => 'Clientes',
            'name' => 'Consultar cartera de clientes',
            'description' => 'Ver listado y ficha detallada de clientes.',
        ],
        'clients.manage' => [
            'module' => 'Clientes',
            'name' => 'Crear, editar y eliminar clientes',
            'description' => 'Registrar nuevos clientes, editar datos y dar de baja.',
        ],
        'products.view' => [
            'module' => 'Catálogo e Inventario',
            'name' => 'Consultar catálogo de productos',
            'description' => 'Ver catálogo, costos, precios y ficha de producto.',
        ],
        'products.manage' => [
            'module' => 'Catálogo e Inventario',
            'name' => 'Gestionar productos',
            'description' => 'Crear, modificar, activar/ocultar y eliminar productos.',
        ],
        'categories.manage' => [
            'module' => 'Catálogo e Inventario',
            'name' => 'Gestionar categorías',
            'description' => 'Crear, editar y organizar categorías comerciales.',
        ],
        'inventory.view' => [
            'module' => 'Catálogo e Inventario',
            'name' => 'Supervisar inventario y alertas',
            'description' => 'Revisar existencias, stock crítico y valorización.',
        ],
        'suppliers.view' => [
            'module' => 'Proveedores',
            'name' => 'Consultar cartera de proveedores',
            'description' => 'Ver directorio de proveedores, plantillas WhatsApp y estadísticas.',
        ],
        'suppliers.manage' => [
            'module' => 'Proveedores',
            'name' => 'Gestionar proveedores y compras',
            'description' => 'Crear/editar proveedores y registrar compras de abastecimiento.',
        ],
        'sales.view' => [
            'module' => 'Ventas y Reportes',
            'name' => 'Ver historial de ventas y finanzas',
            'description' => 'Consultar registros de ventas y utilidades.',
        ],
        'sales.create' => [
            'module' => 'Ventas y Reportes',
            'name' => 'Registrar nuevas ventas',
            'description' => 'Emitir ventas rápidas descontando inventario.',
        ],
        'exports.download' => [
            'module' => 'Ventas y Reportes',
            'name' => 'Exportar reportes CSV / Excel',
            'description' => 'Descargar listados de productos, ventas y clientes.',
        ],
        'contacts.view' => [
            'module' => 'Web Pública',
            'name' => 'Ver consultas web de clientes',
            'description' => 'Leer mensajes enviados desde el formulario público.',
        ],
        'contacts.manage' => [
            'module' => 'Web Pública',
            'name' => 'Atender y eliminar consultas web',
            'description' => 'Actualizar estado de atención o depurar mensajes.',
        ],
        'site.edit' => [
            'module' => 'Web Pública',
            'name' => 'Editar página web pública',
            'description' => 'Configurar textos, visibilidad, preguntas frecuentes y WhatsApp.',
        ],
        'assistant.use' => [
            'module' => 'Asistente NIA',
            'name' => 'Usar asistente operativo NIA',
            'description' => 'Consultar resúmenes o ejecutar comandos rápidos desde NIA.',
        ],
        'users.view' => [
            'module' => 'Seguridad y RBAC',
            'name' => 'Ver usuarios del equipo',
            'description' => 'Consultar cuentas de acceso, roles y estado.',
        ],
        'users.manage' => [
            'module' => 'Seguridad y RBAC',
            'name' => 'Administrar usuarios del equipo',
            'description' => 'Crear cuentas, asignar roles, cambiar claves y activar/suspender.',
        ],
        'roles.manage' => [
            'module' => 'Seguridad y RBAC',
            'name' => 'Administrar roles y permisos (RBAC)',
            'description' => 'Crear, editar y definir la matriz de permisos por rol.',
        ],
    ];

    protected $fillable = ['key', 'module', 'name', 'description'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
