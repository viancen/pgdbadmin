<?php

namespace App\Http\Controllers;

use App\Services\PgConnectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        private PgConnectionService $pg
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        if ($request->session()->has('pg_credentials')) {
            return redirect()->route('home');
        }
        return view('login', ['title' => 'Login', 'showNav' => false]);
    }

    public function store(Request $request): View|RedirectResponse
    {
        $credentials = [
            'host' => trim($request->input('host', 'localhost') ?: 'localhost'),
            'port' => (int) ($request->input('port') ?: 5432),
            'user' => trim($request->input('user', '')),
            'password' => $request->input('password', ''),
            'ssl' => $request->boolean('ssl'),
        ];

        if ($credentials['user'] === '') {
            return view('login', [
                'title' => 'Login',
                'showNav' => false,
                'error' => 'User is required',
                'host' => $credentials['host'],
                'port' => $credentials['port'],
                'user' => $credentials['user'],
                'ssl' => $credentials['ssl'],
            ]);
        }

        $test = $this->pg->testConnection($credentials);
        if (! $test['ok']) {
            return view('login', [
                'title' => 'Login',
                'showNav' => false,
                'error' => $test['message'] ?? 'Connection failed',
                'host' => $credentials['host'],
                'port' => $credentials['port'],
                'user' => $credentials['user'],
                'ssl' => $credentials['ssl'],
            ]);
        }

        $request->session()->put('pg_credentials', $credentials);
        try {
            $dbs = $this->pg->listDatabases($credentials);
        } catch (\Throwable $e) {
            $dbs = [];
        }
        $request->session()->put('pg_databases', $dbs);
        $currentDb = in_array('postgres', $dbs) ? 'postgres' : ($dbs[0] ?? 'postgres');
        $request->session()->put('pg_current_db', $currentDb);

        return redirect()->route('home');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }
}
