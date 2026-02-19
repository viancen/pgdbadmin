<?php

namespace App\Http\Controllers;

use App\Services\PgConnectionService;
use Illuminate\Http\JsonResponse;
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
        $customSql = $request->query('sql') ? trim($request->query('sql')) : null;
        $sort = $request->query('sort') ? trim($request->query('sort')) : null;
        $dir = strtoupper((string) $request->query('dir')) === 'DESC' ? 'DESC' : 'ASC';

        $quotedTable = '"' . str_replace('"', '""', $schema) . '"."' . str_replace('"', '""', $table) . '"';

        try {
            $columns = $this->pg->tableStructure($pdo, $schema, $table);
            $primaryKey = $this->pg->getPrimaryKeyColumns($pdo, $schema, $table);
            $columnNames = array_map(fn ($c) => $c->name, $columns);
            $hasId = in_array('id', $columnNames, true);

            $defaultSql = $hasId
                ? "SELECT * FROM {$quotedTable} ORDER BY \"id\" DESC LIMIT 100"
                : "SELECT * FROM {$quotedTable} LIMIT 100";

            if ($customSql !== null && $customSql !== '') {
                $orderBy = $sort !== null && $sort !== '' ? $sort : null;
                $data = $this->pg->executeSelectWithPagination($pdo, $customSql, $limit, $offset, $orderBy, $dir);
                $sql = $customSql;
            } else {
                $orderBy = $sort !== null && $sort !== '' && in_array($sort, $columnNames, true) ? $sort : ($hasId ? 'id' : null);
                $orderDir = $orderBy === 'id' && $sort === null ? 'DESC' : $dir;
                $data = $this->pg->selectTableRows($pdo, $schema, $table, $limit, $offset, $orderBy, $orderDir);
                $sql = null;
            }
        } catch (\Throwable $e) {
            abort(500, $e->getMessage());
        }

        $sort = $sort ?? ($hasId && ($customSql === null || $customSql === '') ? 'id' : null);
        $dir = ($sort === 'id' && ($customSql === null || $customSql === '') && ($request->query('sort') === null)) ? 'DESC' : $dir;

        return view('table-browse', [
            'title' => "{$schema}.{$table}",
            'schema' => $schema,
            'tableName' => $table,
            'rows' => $data['rows'],
            'fields' => $data['fields'],
            'total' => $data['total'],
            'limit' => $limit,
            'offset' => $offset,
            'sql' => $sql,
            'defaultSql' => $defaultSql,
            'columns' => $columns,
            'primaryKey' => $primaryKey,
            'sort' => $sort,
            'dir' => $dir,
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

    /**
     * Update a single row by primary key (JSON: { pk: {}, updates: {} }).
     */
    public function updateRow(Request $request, string $schema, string $table): JsonResponse
    {
        $credentials = $request->session()->get('pg_credentials');
        $currentDb = $request->session()->get('pg_current_db');
        $pdo = $this->pg->getConnection($credentials, $currentDb);

        if (! $pdo) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        $pk = $request->input('pk', []);
        $updates = $request->input('updates', []);

        if (! is_array($pk) || ! is_array($updates)) {
            return response()->json(['error' => 'Invalid payload: pk and updates must be objects.'], 422);
        }

        try {
            $primaryKey = $this->pg->getPrimaryKeyColumns($pdo, $schema, $table);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        if (empty($primaryKey)) {
            return response()->json(['error' => 'Table has no primary key; row update is not supported.'], 422);
        }

        $pkValues = [];
        foreach ($primaryKey as $col) {
            if (! array_key_exists($col, $pk)) {
                return response()->json(['error' => "Primary key column \"{$col}\" is required."], 422);
            }
            $pkValues[$col] = $pk[$col];
        }

        $allowedUpdates = [];
        $columns = $this->pg->tableStructure($pdo, $schema, $table);
        $columnNames = array_column($columns, 'name');
        foreach ($updates as $col => $value) {
            if (in_array($col, $primaryKey, true)) {
                return response()->json(['error' => "Cannot update primary key column \"{$col}\"."], 422);
            }
            if (! in_array($col, $columnNames, true)) {
                return response()->json(['error' => "Unknown column \"{$col}\"."], 422);
            }
            $allowedUpdates[$col] = $value;
        }

        try {
            $this->pg->updateRow($pdo, $schema, $table, $pkValues, $allowedUpdates);
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
