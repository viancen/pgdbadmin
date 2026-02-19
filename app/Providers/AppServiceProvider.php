<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PgConnectionService::class, fn () => new PgConnectionService);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Sidebar/auth data is shared via HandleInertiaRequests for Vue/Inertia.
    }
}
