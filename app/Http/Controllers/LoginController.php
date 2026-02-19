<?php

namespace App\Http\Controllers;

use App\Services\PgConnectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function __construct(
        private PgConnectionService $pg
    ) {}

    public function show(Request $request): Response|RedirectResponse
    {
        if ($request->session()->has('pg_credentials')) {
            return redirect()->route('home');
        }
        return Inertia::render('Login', ['title' => 'Login']);
    }

    public function store(Request $request): Response|RedirectResponse
    {
        $credentials = [
            'host' => trim($request->input('host', 'localhost') ?: 'localhost'),
            'port' => (int) ($request->input('port') ?: 5432),
            'user' => trim($request->input('user', '')),
            'password' => $request->input('password', ''),
            'ssl' => $request->boolean('ssl'),
        ];

        if ($credentials['user'] === '') {
            return Inertia::render('Login', [
                'title' => 'Login',
                'error' => 'User is required',
                'host' => $credentials['host'],
                'port' => $credentials['port'],
                'user' => $credentials['user'],
                'ssl' => $credentials['ssl'],
            ]);
        }

        $test = $this->pg->testConnection($credentials);
        if (! $test['ok']) {
            return Inertia::render('Login', [
                'title' => 'Login',
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
