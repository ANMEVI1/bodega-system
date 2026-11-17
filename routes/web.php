<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierPurchaseController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'home'])->name('home');
Route::post('/contacto', [SiteController::class, 'contact'])->middleware('throttle:5,1')->name('contact.send');
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

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('clientes', ClientController::class)->parameters(['clientes' => 'client'])->names('clients');
    Route::resource('productos', ProductController::class)->except('show')->parameters(['productos' => 'product'])->names('products');
    Route::resource('proveedores', SupplierController::class)->except('show')->parameters(['proveedores' => 'supplier'])->names('suppliers');
    Route::get('/ventas', [SaleController::class, 'index'])->name('sales.index');
    Route::post('/ventas', [SaleController::class, 'store'])->name('sales.store');
    Route::post('/compras-proveedor', [SupplierPurchaseController::class, 'store'])->name('purchases.store');
    Route::get('/sitio', [SiteController::class, 'editor'])->name('site.edit');
    Route::put('/sitio', [SiteController::class, 'update'])->name('site.update');
    Route::get('/consultas', [ContactController::class, 'index'])->name('contacts.index');
    Route::patch('/consultas/{contact}', [ContactController::class, 'update'])->name('contacts.update');
    Route::delete('/consultas/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');
    Route::get('/exportar', ExportController::class)->name('export');
    Route::post('/asistente', ChatController::class)->middleware('throttle:20,1')->name('chat');
});
