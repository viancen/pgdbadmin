<?php

namespace App\Http\Controllers;

use App\Services\PgConnectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TableController extends Controller
{
    private const DEFAULT_PAGE_SIZE = 100;

    private const MAX_PAGE_SIZE = 500;

    public function __construct(
        private PgConnectionService $pg
    ) {}

    public function browse(Request $request, string $schema, string $table): View|RedirectResponse
    {
        $credentials = $request->session()->get('pg_credentials');
        $currentDb = $request->session()->get('pg_current_db');
        $pdo = $this->pg->getConnection($credentials, $currentDb);

        if (! $pdo) {
            return redirect()->route('login');
        }

        $limit = min((int) ($request->query('limit') ?: self::DEFAULT_PAGE_SIZE), self::MAX_PAGE_SIZE);
        $offset = max(0, (int) ($request->query('offset') ?: 0));

        try {
            $data = $this->pg->selectTableRows($pdo, $schema, $table, $limit, $offset);
        } catch (\Throwable $e) {
            abort(500, $e->getMessage());
        }

        return view('table-browse', [
            'title' => "{$schema}.{$table}",
            'schema' => $schema,
            'tableName' => $table,
            'rows' => $data['rows'],
            'fields' => $data['fields'],
            'total' => $data['total'],
            'limit' => $limit,
            'offset' => $offset,
            'currentDb' => $currentDb,
            'databases' => $request->session()->get('pg_databases', []),
            'user' => $credentials['user'],
            'host' => $credentials['host'],
        ]);
    }

    public function structure(Request $request, string $schema, string $table): View|RedirectResponse
    {
        $credentials = $request->session()->get('pg_credentials');
        $currentDb = $request->session()->get('pg_current_db');
        $pdo = $this->pg->getConnection($credentials, $currentDb);

        if (! $pdo) {
            return redirect()->route('login');
        }

        try {
            $columns = $this->pg->tableStructure($pdo, $schema, $table);
        } catch (\Throwable $e) {
            abort(500, $e->getMessage());
        }

        return view('table-structure', [
            'title' => "Structure {$schema}.{$table}",
            'schema' => $schema,
            'tableName' => $table,
            'columns' => $columns,
            'currentDb' => $currentDb,
            'databases' => $request->session()->get('pg_databases', []),
            'user' => $credentials['user'],
            'host' => $credentials['host'],
        ]);
    }
}
