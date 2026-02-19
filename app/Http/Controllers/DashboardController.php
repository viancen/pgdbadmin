<?php

namespace App\Http\Controllers;

use App\Services\PgConnectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private PgConnectionService $pg
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        $credentials = $request->session()->get('pg_credentials');
        if (! $credentials) {
            return Inertia::render('Landing', ['title' => 'PG Admin']);
        }

        $currentDb = $request->session()->get('pg_current_db');
        $pdo = $this->pg->getConnection($credentials, $currentDb);

        if (! $pdo) {
            return redirect()->route('login');
        }

        try {
            $tables = $this->pg->listTables($pdo);
        } catch (\Throwable $e) {
            return Inertia::render('Dashboard', [
                'title' => 'Dashboard',
                'currentDb' => $currentDb,
                'tables' => [],
                'error' => $e->getMessage(),
            ]);
        }

        return Inertia::render('Dashboard', [
            'title' => 'Dashboard',
            'currentDb' => $currentDb,
            'tables' => $tables,
        ]);
    }

    public function switchDb(Request $request): RedirectResponse
    {
        $db = $request->input('database');
        $databases = $request->session()->get('pg_databases', []);
        if ($db && is_array($databases) && in_array($db, $databases)) {
            $request->session()->put('pg_current_db', $db);
        }
        return redirect()->back();
    }
}
