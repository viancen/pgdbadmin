const { Pool } = require('pg');

/**
 * Create a new connection pool from session credentials.
 * No database name = connect to default (postgres) to list databases.
 */
function createPool(credentials, database = null) {
  const config = {
    host: credentials.host || 'localhost',
    port: parseInt(credentials.port, 10) || 5432,
    user: credentials.user,
    password: credentials.password,
    ssl: credentials.ssl === true || credentials.ssl === 'true' ? { rejectUnauthorized: false } : false,
  };
  if (database) config.database = database;
  else config.database = 'postgres';
  return new Pool({
    ...config,
    max: 5,
    idleTimeoutMillis: 30000,
    connectionTimeoutMillis: 10000,
  });
}

/**
 * Test connection with given credentials (no specific database).
 */
async function testConnection(credentials) {
  const pool = createPool(credentials);
  try {
    const client = await pool.connect();
    await client.query('SELECT 1');
    client.release();
    await pool.end();
    return { ok: true };
  } catch (err) {
    try { await pool.end(); } catch (_) {}
    return { ok: false, message: err.message };
  }
}

/**
 * Get list of databases (user must have permission).
 */
async function listDatabases(credentials) {
  const pool = createPool(credentials);
  try {
    const res = await pool.query(`
      SELECT datname AS name
      FROM pg_database
      WHERE datistemplate = false
      ORDER BY datname
    `);
    await pool.end();
    return res.rows.map((r) => r.name);
  } catch (err) {
    try { await pool.end(); } catch (_) {}
    throw err;
  }
}

/**
 * Execute query with optional limit/offset. For total count, use countQuery separately.
 */
async function query(pool, sql, params = [], limit = 100, offset = 0) {
  const hasParams = params.length > 0;
  const limitParam = hasParams ? params.length + 1 : 1;
  const offsetParam = hasParams ? params.length + 2 : 2;
  const paginatedSql = `${sql} LIMIT $${limitParam} OFFSET $${offsetParam}`;
  const res = await pool.query(paginatedSql, [...params, limit, offset]);
  return { rows: res.rows, fields: res.fields };
}

/**
 * Run COUNT(*) for a table (schema/table from our list, safe to quote).
 */
function quoteIdent(name) {
  return '"' + String(name).replace(/"/g, '""') + '"';
}

async function countTableRows(pool, schema, table) {
  const quoted = `${quoteIdent(schema)}.${quoteIdent(table)}`;
  const res = await pool.query(`SELECT COUNT(*) AS count FROM ${quoted}`, []);
  return parseInt(res.rows[0].count, 10);
}

/**
 * Select rows from a table with pagination.
 */
async function selectTableRows(pool, schema, table, limit = 100, offset = 0) {
  const quoted = `${quoteIdent(schema)}.${quoteIdent(table)}`;
  const [dataRes, countRes] = await Promise.all([
    pool.query(`SELECT * FROM ${quoted} LIMIT $1 OFFSET $2`, [limit, offset]),
    pool.query(`SELECT COUNT(*) AS count FROM ${quoted}`, []),
  ]);
  const total = parseInt(countRes.rows[0].count, 10);
  return { rows: dataRes.rows, fields: dataRes.fields, total };
}

/**
 * Get tables in current database.
 */
async function listTables(pool) {
  const res = await pool.query(`
    SELECT
      schemaname AS schema,
      tablename AS name,
      (SELECT COUNT(*) FROM information_schema.columns c
       WHERE c.table_schema = t.schemaname AND c.table_name = t.tablename) AS column_count
    FROM pg_catalog.pg_tables t
    WHERE schemaname NOT IN ('pg_catalog', 'information_schema')
    ORDER BY schemaname, tablename
  `);
  return res.rows;
}

/**
 * Get table columns (structure).
 */
async function tableStructure(pool, schema, table) {
  const res = await pool.query(`
    SELECT
      a.attname AS name,
      pg_catalog.format_type(a.atttypid, a.atttypmod) AS type,
      a.attnotnull AS notnull,
      pg_get_expr(d.adbin, d.adrelid) AS default
    FROM pg_catalog.pg_attribute a
    LEFT JOIN pg_catalog.pg_attrdef d ON (a.attrelid, a.attnum) = (d.adrelid, d.adnum)
    WHERE a.attrelid = ($1::text || '.' || $2::text)::regclass
      AND a.attnum > 0
      AND NOT a.attisdropped
    ORDER BY a.attnum
  `, [schema, table]);
  return res.rows;
}

/**
 * Get approximate row count for a table (fast).
 */
async function tableRowCount(pool, schema, table) {
  const res = await pool.query(
    `SELECT reltuples::bigint AS count FROM pg_class c
     JOIN pg_namespace n ON n.oid = c.relnamespace
     WHERE n.nspname = $1 AND c.relname = $2 AND c.relkind = 'r'`,
    [schema, table]
  );
  return res.rows[0] ? parseInt(res.rows[0].count, 10) : 0;
}

module.exports = {
  createPool,
  testConnection,
  listDatabases,
  query,
  listTables,
  tableStructure,
  tableRowCount,
  countTableRows,
  selectTableRows,
  quoteIdent,
};
