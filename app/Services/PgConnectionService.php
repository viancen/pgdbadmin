<?php

namespace App\Services;

use PDO;
use PDOException;

class PgConnectionService
{
    /**
     * Test connection with given credentials (no specific database).
     */
    public function testConnection(array $credentials): array
    {
        $dsn = $this->buildDsn($credentials, 'postgres');
        try {
            $pdo = new PDO($dsn, $credentials['user'], $credentials['password'] ?? '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10,
            ]);
            $pdo->query('SELECT 1');
            return ['ok' => true];
        } catch (PDOException $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get list of databases (user must have permission).
     *
     * @return array<int, string>
     */
    public function listDatabases(array $credentials): array
    {
        $pdo = $this->connect($credentials, null);
        $stmt = $pdo->query("
            SELECT datname AS name
            FROM pg_database
            WHERE datistemplate = false
            ORDER BY datname
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $rows ?: [];
    }

    /**
     * Get PDO connection for current session credentials and database.
     */
    public function getConnection(array $credentials, ?string $database): ?PDO
    {
        if (! $database) {
            return null;
        }
        try {
            return $this->connect($credentials, $database);
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Get tables in current database.
     *
     * @return array<int, object{schema: string, name: string, column_count: string}>
     */
    public function listTables(PDO $pdo): array
    {
        $stmt = $pdo->query("
            SELECT
                schemaname AS schema,
                tablename AS name,
                (SELECT COUNT(*) FROM information_schema.columns c
                 WHERE c.table_schema = t.schemaname AND c.table_name = t.tablename) AS column_count
            FROM pg_catalog.pg_tables t
            WHERE schemaname NOT IN ('pg_catalog', 'information_schema')
            ORDER BY schemaname, tablename
        ");
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Select rows from a table with pagination.
     *
     * @param  string|null  $orderBy  column name to order by (quoted); null = no order
     * @param  string  $orderDir  ASC or DESC
     * @return array{rows: array, fields: array, total: int}
     */
    public function selectTableRows(PDO $pdo, string $schema, string $table, int $limit = 100, int $offset = 0, ?string $orderBy = null, string $orderDir = 'ASC'): array
    {
        $quoted = $this->quoteIdent($schema) . '.' . $this->quoteIdent($table);
        $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';
        $orderClause = $orderBy !== null && $orderBy !== '' ? ' ORDER BY ' . $this->quoteIdent($orderBy) . ' ' . $orderDir : '';
        $dataStmt = $pdo->prepare("SELECT * FROM {$quoted}{$orderClause} LIMIT ? OFFSET ?");
        $dataStmt->execute([$limit, $offset]);
        $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
        $countStmt = $pdo->query("SELECT COUNT(*) AS count FROM {$quoted}");
        $total = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
        $fields = $rows ? array_map(fn ($key) => (object) ['name' => $key], array_keys($rows[0])) : [];
        return ['rows' => $rows, 'fields' => $fields, 'total' => $total];
    }

    /**
     * Get primary key column names for a table.
     *
     * @return array<int, string>
     */
    public function getPrimaryKeyColumns(PDO $pdo, string $schema, string $table): array
    {
        $stmt = $pdo->prepare("
            SELECT a.attname AS name
            FROM pg_catalog.pg_attribute a
            JOIN pg_catalog.pg_index i ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey) AND a.attnum > 0
            WHERE i.indrelid = (?::text || '.' || ?::text)::regclass
              AND i.indisprimary
              AND NOT a.attisdropped
            ORDER BY array_position(i.indkey, a.attnum)
        ");
        $stmt->execute([$schema, $table]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Execute a SELECT with pagination: get total count and paged rows.
     * Injects ORDER BY (optional), LIMIT/OFFSET into the SQL.
     *
     * @param  string|null  $orderBy  column name to order by (quoted)
     * @param  string  $orderDir  ASC or DESC
     * @return array{rows: array, fields: array, total: int}
     */
    public function executeSelectWithPagination(PDO $pdo, string $sql, int $limit = 100, int $offset = 0, ?string $orderBy = null, string $orderDir = 'ASC'): array
    {
        $sql = trim(rtrim($sql, ';'));
        $sqlForCount = preg_replace('/\s*OFFSET\s+\d+/i', '', $sql);
        $sqlForCount = preg_replace('/\s*LIMIT\s+\d+/i', '', $sqlForCount);
        $countSql = "SELECT COUNT(*) AS count FROM ({$sqlForCount}) AS _cnt";
        $countStmt = $pdo->query($countSql);
        $total = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['count'];

        $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';
        $orderClause = ($orderBy !== null && $orderBy !== '') ? ' ORDER BY ' . $this->quoteIdent($orderBy) . ' ' . $orderDir : '';

        $dataSql = preg_replace('/\s*OFFSET\s+\d+/i', '', $sql);
        $dataSql = preg_replace('/\s*LIMIT\s+\d+/i', '', $dataSql);
        // Remove existing ORDER BY so we can inject our sort (user sort takes precedence)
        $dataSql = preg_replace('/\s*ORDER BY\s+.+?(?=\s*LIMIT\s|\s*$)/is', '', $dataSql);
        $dataSql = rtrim(rtrim($dataSql), ';');
        $dataSql .= "{$orderClause} LIMIT {$limit} OFFSET {$offset}";
        $stmt = $pdo->query($dataSql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $fields = $rows ? array_map(fn ($key) => (object) ['name' => $key], array_keys($rows[0])) : [];
        if (empty($rows) && $stmt->columnCount() > 0) {
            for ($i = 0; $i < $stmt->columnCount(); $i++) {
                $meta = $stmt->getColumnMeta($i);
                $fields[] = (object) ['name' => $meta['name'] ?? "column_{$i}"];
            }
        }
        return ['rows' => $rows, 'fields' => $fields, 'total' => $total];
    }

    /**
     * Update a single row by primary key.
     *
     * @param  array<string, mixed>  $pkValues  primary key column => value
     * @param  array<string, mixed>  $updates   column => new value (only non-PK columns)
     */
    public function updateRow(PDO $pdo, string $schema, string $table, array $pkValues, array $updates): void
    {
        $quoted = $this->quoteIdent($schema) . '.' . $this->quoteIdent($table);
        $setParts = [];
        $params = [];
        $n = 0;
        foreach ($updates as $col => $value) {
            $n++;
            $setParts[] = $this->quoteIdent($col) . " = ?";
            $params[] = $value === '' || (is_string($value) && strtoupper(trim($value)) === 'NULL') ? null : $value;
        }
        $whereParts = [];
        foreach ($pkValues as $col => $value) {
            $n++;
            $whereParts[] = $this->quoteIdent($col) . " = ?";
            $params[] = $value;
        }
        $sql = "UPDATE {$quoted} SET " . implode(', ', $setParts) . " WHERE " . implode(' AND ', $whereParts);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Get table columns (structure).
     *
     * @return array<int, object{name: string, type: string, notnull: bool, default: ?string}>
     */
    public function tableStructure(PDO $pdo, string $schema, string $table): array
    {
        $stmt = $pdo->prepare("
            SELECT
                a.attname AS name,
                pg_catalog.format_type(a.atttypid, a.atttypmod) AS type,
                a.attnotnull AS notnull,
                pg_get_expr(d.adbin, d.adrelid) AS default
            FROM pg_catalog.pg_attribute a
            LEFT JOIN pg_catalog.pg_attrdef d ON (a.attrelid, a.attnum) = (d.adrelid, d.adnum)
            WHERE a.attrelid = (?::text || '.' || ?::text)::regclass
              AND a.attnum > 0
              AND NOT a.attisdropped
            ORDER BY a.attnum
        ");
        $stmt->execute([$schema, $table]);
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
        foreach ($rows as $row) {
            $row->notnull = (bool) $row->notnull;
        }
        return $rows;
    }

    /**
     * Execute raw SQL and return result for SELECT, or row count for other statements.
     *
     * @param  string|null  $orderBy  column name to order by (for SELECT only)
     * @param  string  $orderDir  ASC or DESC
     * @return array{rows?: array, fields?: array, rowCount?: int, total?: int, limit?: int, offset?: int}|array{message: string}
     */
    public function executeQuery(PDO $pdo, string $sql, int $limit = 500, int $offset = 0, ?string $orderBy = null, string $orderDir = 'ASC'): array
    {
        $isSelect = preg_match('/^\s*SELECT\s+/i', $sql) === 1;
        if ($isSelect) {
            $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';
            $orderClause = ($orderBy !== null && $orderBy !== '') ? ' ORDER BY ' . $this->quoteIdent($orderBy) . ' ' . $orderDir : '';

            $sql = trim(rtrim($sql, ';'));
            $sql = preg_replace('/\s*OFFSET\s+\d+/i', '', $sql);
            $sql = preg_replace('/\s*LIMIT\s+\d+/i', '', $sql);
            $sql = preg_replace('/\s*ORDER BY\s+.+?(?=\s*LIMIT\s|\s*$)/is', '', $sql);
            $sql = rtrim($sql, '; ');
            $sql .= $orderClause . " LIMIT {$limit} OFFSET {$offset}";

            $stmt = $pdo->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $fields = [];
            if ($rows) {
                $fields = array_map(fn ($key) => (object) ['name' => $key], array_keys($rows[0]));
            } else {
                for ($i = 0; $i < $stmt->columnCount(); $i++) {
                    $meta = $stmt->getColumnMeta($i);
                    $fields[] = (object) ['name' => $meta['name'] ?? "column_{$i}"];
                }
            }
            return [
                'rows' => $rows,
                'fields' => $fields,
                'rowCount' => count($rows),
                'total' => count($rows),
                'limit' => $limit,
                'offset' => $offset,
            ];
        }
        $stmt = $pdo->query($sql);
        $count = $stmt ? $stmt->rowCount() : 0;
        $message = $count >= 0 ? "Command executed. Rows affected: {$count}" : 'Command executed.';
        return ['message' => $message];
    }

    private function connect(array $credentials, ?string $database): PDO
    {
        $db = $database ?? 'postgres';
        $dsn = $this->buildDsn($credentials, $db);
        return new PDO($dsn, $credentials['user'], $credentials['password'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10,
        ]);
    }

    private function buildDsn(array $credentials, string $database): string
    {
        $host = $credentials['host'] ?? 'localhost';
        $port = (int) ($credentials['port'] ?? 5432);
        $params = "host={$host} port={$port} dbname={$database}";
        if (! empty($credentials['ssl'])) {
            $params .= ' sslmode=require';
        }
        return "pgsql:{$params}";
    }

    private function quoteIdent(string $name): string
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }
}
