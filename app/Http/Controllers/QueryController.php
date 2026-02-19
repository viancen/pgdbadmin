<?php

namespace App\Http\Controllers;

use App\Services\PgConnectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QueryController extends Controller
{
    private const MAX_PAGE_SIZE = 500;

    public function __construct(
        private PgConnectionService $pg
    ) {}

    public function index(Request $request): View
    {
        $sql = $request->query('table') ? 'SELECT * FROM ' . $request->query('table') . ' LIMIT 100' : '';
        return view('query', [
            'title' => 'SQL Query',
            'sql' => $sql,
            'currentDb' => $request->session()->get('pg_current_db'),
            'databases' => $request->session()->get('pg_databases', []),
            'user' => $request->session()->get('pg_credentials')['user'] ?? '',
            'host' => $request->session()->get('pg_credentials')['host'] ?? '',
        ]);
    }

    public function execute(Request $request): View|RedirectResponse
    {
        $credentials = $request->session()->get('pg_credentials');
        $currentDb = $request->session()->get('pg_current_db');
        $pdo = $this->pg->getConnection($credentials, $currentDb);

        if (! $pdo) {
            return redirect()->route('login');
        }

        $sql = trim($request->input('sql', ''));
        if ($sql === '') {
            return redirect()->route('query.index');
        }

        // If the query contains LIMIT n, respect it but cap at MAX_PAGE_SIZE; otherwise default to MAX to avoid unbounded fetches
        $userLimit = null;
        if (preg_match('/\bLIMIT\s+(\d+)/i', $sql, $m)) {
            $userLimit = (int) $m[1];
        }
        $maxRows = $userLimit !== null ? min($userLimit, self::MAX_PAGE_SIZE) : self::MAX_PAGE_SIZE;
        $limit = min((int) ($request->input('limit') ?: $maxRows), $maxRows);
        $offset = max(0, (int) ($request->input('offset') ?: 0));
        $sort = $request->input('sort') ? trim($request->input('sort')) : null;
        $dir = strtoupper((string) $request->input('dir')) === 'DESC' ? 'DESC' : 'ASC';

        $viewData = [
            'title' => 'SQL Query',
            'sql' => $sql,
            'currentDb' => $currentDb,
            'databases' => $request->session()->get('pg_databases', []),
            'user' => $credentials['user'],
            'host' => $credentials['host'],
            'sort' => $sort,
            'dir' => $dir,
        ];

        try {
            $result = $this->pg->executeQuery($pdo, $sql, $limit, $offset, $sort, $dir);
            if (isset($result['message'])) {
                $viewData['message'] = $result['message'];
            } else {
                $viewData['result'] = $result;
                $viewData['schemas'] = $this->pg->listSchemas($pdo);
            }
        } catch (\Throwable $e) {
            $viewData['error'] = $e->getMessage();
        }

        return view('query', $viewData);
    }

    /**
     * Create a view from the current SELECT query (JSON: schema, name, sql).
     */
    public function createView(Request $request): JsonResponse
    {
        $credentials = $request->session()->get('pg_credentials');
        $currentDb = $request->session()->get('pg_current_db');
        $pdo = $this->pg->getConnection($credentials, $currentDb);

        if (! $pdo) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        $schema = trim($request->input('schema', 'public'));
        $name = trim($request->input('name', ''));
        $sql = $request->input('sql', '');

        if ($name === '') {
            return response()->json(['error' => 'View name is required.'], 422);
        }
        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            return response()->json(['error' => 'View name must be a valid identifier.'], 422);
        }
        if ($schema === '') {
            return response()->json(['error' => 'Schema is required.'], 422);
        }

        try {
            $this->pg->createView($pdo, $schema, $name, $sql);
            return response()->json(['success' => true, 'message' => "View {$schema}.{$name} created."]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
