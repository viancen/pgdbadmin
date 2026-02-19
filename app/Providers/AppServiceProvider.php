<?php

namespace App\Providers;

use App\Services\PgConnectionService;
use Illuminate\Support\Facades\View;
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
        View::composer('layout', function ($view) {
            if (! session()->has('pg_credentials')) {
                return;
            }
            $credentials = session('pg_credentials');
            $currentDb = session('pg_current_db');
            $databases = session('pg_databases', []);
            $pg = app(PgConnectionService::class);
            $pdo = $pg->getConnection($credentials, $currentDb);
            $tables = [];
            if ($pdo) {
                try {
                    $tables = $pg->listTables($pdo);
                } catch (\Throwable $e) {
                    //
                }
            }
            $view->with([
                'sidebarTables' => $tables,
                'sidebarCurrentDb' => $currentDb,
                'sidebarDatabases' => $databases,
                'sidebarUser' => $credentials['user'] ?? '',
                'sidebarHost' => $credentials['host'] ?? '',
            ]);
        });
    }
}
