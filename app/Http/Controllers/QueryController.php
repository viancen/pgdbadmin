<?php

namespace App\Http\Controllers;

use App\Services\PgConnectionService;
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

        $limit = min((int) ($request->input('limit') ?: 500), self::MAX_PAGE_SIZE);
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
            }
        } catch (\Throwable $e) {
            $viewData['error'] = $e->getMessage();
        }

        return view('query', $viewData);
    }
}
