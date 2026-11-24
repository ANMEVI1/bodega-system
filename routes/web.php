<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PublicChatController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierPurchaseController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'home'])->name('home');
Route::post('/contacto', [SiteController::class, 'contact'])->middleware('throttle:5,1')->name('contact.send');
Route::post('/asistente-tienda', PublicChatController::class)->middleware('throttle:30,1')->name('site.chat');
Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::get('/setup', [AuthController::class, 'setup'])->name('setup');
Route::post('/setup', [AuthController::class, 'createFirstAdmin'])->middleware('throttle:5,1')->name('setup.store');
Route::post('/login', [AuthController::class, 'authenticate'])->middleware('throttle:6,1')->name('login.store');
Route::get('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware('throttle:3,1')->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'resetPasswordForm'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.update');
Route::get('/auth/google/redirect', [AuthController::class, 'googleRedirect'])->name('google.redirect');
Route::get('/auth/google/callback', [AuthController::class, 'googleCallback'])->name('google.callback');

Route::middleware(['auth', 'permission'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    // Clientes
    Route::get('/clientes', [ClientController::class, 'index'])
        ->middleware('permission:clients.view|clients.manage')
        ->name('clients.index');
    Route::get('/clientes/create', [ClientController::class, 'create'])
        ->middleware('permission:clients.manage')
        ->name('clients.create');
    Route::post('/clientes', [ClientController::class, 'store'])
        ->middleware('permission:clients.manage')
        ->name('clients.store');
    Route::get('/clientes/{client}', [ClientController::class, 'show'])
        ->middleware('permission:clients.view|clients.manage')
        ->name('clients.show');
    Route::get('/clientes/{client}/edit', [ClientController::class, 'edit'])
        ->middleware('permission:clients.manage')
        ->name('clients.edit');
    Route::match(['put', 'patch'], '/clientes/{client}', [ClientController::class, 'update'])
        ->middleware('permission:clients.manage')
        ->name('clients.update');
    Route::delete('/clientes/{client}', [ClientController::class, 'destroy'])
        ->middleware('permission:clients.manage')
        ->name('clients.destroy');

    // Productos
    Route::get('/productos', [ProductController::class, 'index'])
        ->middleware('permission:products.view|products.manage')
        ->name('products.index');
    Route::get('/productos/create', [ProductController::class, 'create'])
        ->middleware('permission:products.manage')
        ->name('products.create');
    Route::post('/productos', [ProductController::class, 'store'])
        ->middleware('permission:products.manage')
        ->name('products.store');
    Route::get('/productos/{product}', [ProductController::class, 'show'])
        ->middleware('permission:products.view|products.manage')
        ->name('products.show');
    Route::get('/productos/{product}/edit', [ProductController::class, 'edit'])
        ->middleware('permission:products.manage')
        ->name('products.edit');
    Route::match(['put', 'patch'], '/productos/{product}', [ProductController::class, 'update'])
        ->middleware('permission:products.manage')
        ->name('products.update');
    Route::delete('/productos/{product}', [ProductController::class, 'destroy'])
        ->middleware('permission:products.manage')
        ->name('products.destroy');

    // Categorías
    Route::get('/categorias', [CategoryController::class, 'index'])
        ->middleware('permission:categories.manage|products.view|products.manage')
        ->name('categories.index');
    Route::get('/categorias/create', [CategoryController::class, 'create'])
        ->middleware('permission:categories.manage')
        ->name('categories.create');
    Route::post('/categorias', [CategoryController::class, 'store'])
        ->middleware('permission:categories.manage')
        ->name('categories.store');
    Route::get('/categorias/{category}/edit', [CategoryController::class, 'edit'])
        ->middleware('permission:categories.manage')
        ->name('categories.edit');
    Route::match(['put', 'patch'], '/categorias/{category}', [CategoryController::class, 'update'])
        ->middleware('permission:categories.manage')
        ->name('categories.update');
    Route::delete('/categorias/{category}', [CategoryController::class, 'destroy'])
        ->middleware('permission:categories.manage')
        ->name('categories.destroy');

    // Inventario
    Route::get('/inventario', [InventoryController::class, 'index'])
        ->middleware('permission:inventory.view|products.manage')
        ->name('inventory.index');

    // Proveedores y compras
    Route::get('/proveedores', [SupplierController::class, 'index'])
        ->middleware('permission:suppliers.view|suppliers.manage')
        ->name('suppliers.index');
    Route::get('/proveedores/create', [SupplierController::class, 'create'])
        ->middleware('permission:suppliers.manage')
        ->name('suppliers.create');
    Route::post('/proveedores', [SupplierController::class, 'store'])
        ->middleware('permission:suppliers.manage')
        ->name('suppliers.store');
    Route::get('/proveedores/{supplier}/edit', [SupplierController::class, 'edit'])
        ->middleware('permission:suppliers.manage')
        ->name('suppliers.edit');
    Route::match(['put', 'patch'], '/proveedores/{supplier}', [SupplierController::class, 'update'])
        ->middleware('permission:suppliers.manage')
        ->name('suppliers.update');
    Route::delete('/proveedores/{supplier}', [SupplierController::class, 'destroy'])
        ->middleware('permission:suppliers.manage')
        ->name('suppliers.destroy');
    Route::post('/compras-proveedor', [SupplierPurchaseController::class, 'store'])
        ->middleware('permission:suppliers.manage|products.manage')
        ->name('purchases.store');

    // Ventas
    Route::get('/ventas', [SaleController::class, 'index'])
        ->middleware('permission:sales.view|sales.create')
        ->name('sales.index');
    Route::post('/ventas', [SaleController::class, 'store'])
        ->middleware('permission:sales.create')
        ->name('sales.store');

    // Página web pública
    Route::get('/sitio', [SiteController::class, 'editor'])
        ->middleware('permission:site.edit')
        ->name('site.edit');
    Route::put('/sitio', [SiteController::class, 'update'])
        ->middleware('permission:site.edit')
        ->name('site.update');

    // Consultas web
    Route::get('/consultas', [ContactController::class, 'index'])
        ->middleware('permission:contacts.view|contacts.manage')
        ->name('contacts.index');
    Route::patch('/consultas/{contact}', [ContactController::class, 'update'])
        ->middleware('permission:contacts.manage')
        ->name('contacts.update');
    Route::delete('/consultas/{contact}', [ContactController::class, 'destroy'])
        ->middleware('permission:contacts.manage')
        ->name('contacts.destroy');

    // Exportación y asistente NIA
    Route::get('/exportar', ExportController::class)
        ->middleware('permission:exports.download')
        ->name('export');
    Route::post('/asistente', ChatController::class)
        ->middleware(['throttle:20,1', 'permission:assistant.use'])
        ->name('chat');

    // Seguridad: Usuarios y RBAC Roles
    Route::get('/usuarios', [UserController::class, 'index'])
        ->middleware('permission:users.view|users.manage')
        ->name('users.index');
    Route::get('/usuarios/create', [UserController::class, 'create'])
        ->middleware('permission:users.manage')
        ->name('users.create');
    Route::post('/usuarios', [UserController::class, 'store'])
        ->middleware('permission:users.manage')
        ->name('users.store');
    Route::get('/usuarios/{user}/edit', [UserController::class, 'edit'])
        ->middleware('permission:users.manage')
        ->name('users.edit');
    Route::match(['put', 'patch'], '/usuarios/{user}', [UserController::class, 'update'])
        ->middleware('permission:users.manage')
        ->name('users.update');
    Route::patch('/usuarios/{user}/estado', [UserController::class, 'toggleStatus'])
        ->middleware('permission:users.manage')
        ->name('users.toggle');
    Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])
        ->middleware('permission:users.manage')
        ->name('users.destroy');

    Route::get('/roles', [RoleController::class, 'index'])
        ->middleware('permission:roles.manage')
        ->name('roles.index');
    Route::get('/roles/create', [RoleController::class, 'create'])
        ->middleware('permission:roles.manage')
        ->name('roles.create');
    Route::post('/roles', [RoleController::class, 'store'])
        ->middleware('permission:roles.manage')
        ->name('roles.store');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])
        ->middleware('permission:roles.manage')
        ->name('roles.edit');
    Route::match(['put', 'patch'], '/roles/{role}', [RoleController::class, 'update'])
        ->middleware('permission:roles.manage')
        ->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
        ->middleware('permission:roles.manage')
        ->name('roles.destroy');
});
