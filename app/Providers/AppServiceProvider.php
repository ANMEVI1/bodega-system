<?php

namespace App\Providers;

use App\Models\ContactSubmission;
use App\Models\Permission;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale('es');

        Gate::before(function (User $user, string $ability): ?bool {
            if (array_key_exists($ability, Permission::CATALOG)) {
                return $user->hasPermission($ability);
            }

            return null;
        });

        View::composer('layouts.admin', function ($view): void {
            if (! Auth::check()) {
                return;
            }

            $navNewContacts = Schema::hasTable('contact_submissions')
                ? ContactSubmission::query()->where('status', 'new')->count()
                : 0;
            $navLowStock = Schema::hasTable('products')
                ? Product::query()->where('active', true)->whereColumn('stock', '<=', 'low_stock_at')->count()
                : 0;

            $view->with(compact('navNewContacts', 'navLowStock'));
        });
    }
}
