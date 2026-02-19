<?php

namespace App\Http\Middleware;

use App\Services\PgConnectionService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $shared = [
            ...parent::share($request),
        ];

        if ($request->session()->has('pg_credentials')) {
            $credentials = $request->session()->get('pg_credentials');
            $currentDb = $request->session()->get('pg_current_db');
            $databases = $request->session()->get('pg_databases', []);
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
            $shared['auth'] = [
                'sidebarTables' => $tables,
                'sidebarCurrentDb' => $currentDb,
                'sidebarDatabases' => $databases,
                'sidebarUser' => $credentials['user'] ?? '',
                'sidebarHost' => $credentials['host'] ?? '',
            ];
        } else {
            $shared['auth'] = null;
        }

        return $shared;
    }
}
