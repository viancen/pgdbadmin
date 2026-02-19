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
     * @return array{rows: array, fields: array, total: int}
     */
    public function selectTableRows(PDO $pdo, string $schema, string $table, int $limit = 100, int $offset = 0): array
    {
        $quoted = $this->quoteIdent($schema) . '.' . $this->quoteIdent($table);
        $dataStmt = $pdo->prepare("SELECT * FROM {$quoted} LIMIT ? OFFSET ?");
        $dataStmt->execute([$limit, $offset]);
        $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
        $countStmt = $pdo->query("SELECT COUNT(*) AS count FROM {$quoted}");
        $total = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
        $fields = $rows ? array_map(fn ($key) => (object) ['name' => $key], array_keys($rows[0])) : [];
        return ['rows' => $rows, 'fields' => $fields, 'total' => $total];
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
     * @return array{rows?: array, fields?: array, rowCount?: int, total?: int, limit?: int, offset?: int}|array{message: string}
     */
    public function executeQuery(PDO $pdo, string $sql, int $limit = 500, int $offset = 0): array
    {
        $isSelect = preg_match('/^\s*SELECT\s+/i', $sql) === 1;
        if ($isSelect) {
            $hasLimit = (bool) preg_match('/\bLIMIT\s+\d+/i', $sql);
            $sql = $hasLimit ? $sql : rtrim(rtrim($sql, ';')) . " LIMIT {$limit} OFFSET {$offset}";
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
