<?php

namespace App\Http\Controllers;

use App\Services\PgConnectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private PgConnectionService $pg
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $credentials = $request->session()->get('pg_credentials');
        if (! $credentials) {
            return view('landing', ['title' => 'PG Admin', 'showNav' => false]);
        }

        $currentDb = $request->session()->get('pg_current_db');
        $pdo = $this->pg->getConnection($credentials, $currentDb);

        if (! $pdo) {
            return redirect()->route('login');
        }

        try {
            $tables = $this->pg->listTables($pdo);
        } catch (\Throwable $e) {
            $tables = [];
            return view('dashboard', [
                'title' => 'Dashboard',
                'showNav' => true,
                'currentDb' => $currentDb,
                'databases' => $request->session()->get('pg_databases', []),
                'user' => $credentials['user'],
                'host' => $credentials['host'],
                'tables' => [],
                'error' => $e->getMessage(),
            ]);
        }

        return view('dashboard', [
            'title' => 'Dashboard',
            'showNav' => true,
            'currentDb' => $currentDb,
            'databases' => $request->session()->get('pg_databases', []),
            'user' => $credentials['user'],
            'host' => $credentials['host'],
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
