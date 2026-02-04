require('dotenv').config();
const path = require('path');
const express = require('express');
const session = require('express-session');
const { createPool, testConnection, listDatabases, listTables, tableStructure, selectTableRows } = require('./lib/db');

const PORT = process.env.PORT || 3000;
const app = express();

app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));

app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(express.static(path.join(__dirname, 'public')));

app.use(
  session({
    secret: process.env.SESSION_SECRET || 'pgdbadmin-secret-change-in-production',
    resave: false,
    saveUninitialized: false,
    cookie: { secure: process.env.NODE_ENV === 'production', httpOnly: true, maxAge: 24 * 60 * 60 * 1000 },
  })
);

// Simple CSRF token (session-based)
app.use((req, res, next) => {
  if (!req.session.csrf) req.session.csrf = require('crypto').randomBytes(24).toString('hex');
  res.locals.csrf = req.session.csrf;
  next();
});

function requireAuth(req, res, next) {
  if (!req.session.credentials) return res.redirect('/login');
  next();
}

function getPool(req) {
  if (!req.session.credentials || !req.session.currentDb) return null;
  return createPool(req.session.credentials, req.session.currentDb);
}

// Render with layout
function render(res, partialPath, options = {}) {
  return res.render('layout', {
    partialPath,
    showNav: options.showNav !== false,
    ...options,
  });
}

// ----- Routes -----

app.get('/login', (req, res) => {
  if (req.session.credentials) return res.redirect('/');
  render(res, 'login', { title: 'Login', showNav: false });
});

app.post('/login', async (req, res) => {
  const { host, port, user, password, ssl } = req.body;
  const credentials = {
    host: (host || 'localhost').trim(),
    port: port ? parseInt(port, 10) : 5432,
    user: (user || '').trim(),
    password: password || '',
    ssl: ssl === '1' || ssl === true,
  };
  if (!credentials.user) {
    return render(res, 'login', { title: 'Login', showNav: false, error: 'User is required', ...credentials });
  }
  const test = await testConnection(credentials);
  if (!test.ok) {
    return render(res, 'login', {
      title: 'Login',
      showNav: false,
      error: test.message || 'Connection failed',
      host: credentials.host,
      port: credentials.port,
      user: credentials.user,
      ssl: credentials.ssl,
    });
  }
  req.session.credentials = credentials;
  const dbs = await listDatabases(credentials).catch(() => []);
  req.session.databases = dbs;
  req.session.currentDb = dbs.includes('postgres') ? 'postgres' : (dbs[0] || 'postgres');
  return res.redirect('/');
});

app.post('/logout', (req, res) => {
  req.session.destroy(() => res.redirect('/login'));
});

app.post('/switch-db', requireAuth, (req, res) => {
  const db = req.body.database;
  if (db && req.session.databases && req.session.databases.includes(db)) {
    req.session.currentDb = db;
  }
  return res.redirect(req.get('Referer') || '/');
});

app.get('/', requireAuth, async (req, res) => {
  const pool = getPool(req);
  if (!pool) return res.redirect('/login');
  try {
    const tables = await listTables(pool);
    return render(res, 'dashboard', {
      title: 'Dashboard',
      currentDb: req.session.currentDb,
      databases: req.session.databases || [],
      user: req.session.credentials.user,
      host: req.session.credentials.host,
      tables,
    });
  } catch (err) {
    return render(res, 'dashboard', {
      title: 'Dashboard',
      currentDb: req.session.currentDb,
      databases: req.session.databases || [],
      user: req.session.credentials.user,
      host: req.session.credentials.host,
      tables: [],
      error: err.message,
    });
  } finally {
    pool?.end?.();
  }
});

const DEFAULT_PAGE_SIZE = 100;
const MAX_PAGE_SIZE = 500;

app.get('/table/:schema/:table', requireAuth, async (req, res) => {
  const pool = getPool(req);
  if (!pool) return res.redirect('/login');
  const { schema, table } = req.params;
  const limit = Math.min(parseInt(req.query.limit, 10) || DEFAULT_PAGE_SIZE, MAX_PAGE_SIZE);
  const offset = Math.max(0, parseInt(req.query.offset, 10) || 0);
  try {
    const { rows, fields, total } = await selectTableRows(pool, schema, table, limit, offset);
    return render(res, 'table-browse', {
      title: `${schema}.${table}`,
      schema,
      tableName: table,
      rows,
      fields: fields || [],
      total,
      limit,
      offset,
      currentDb: req.session.currentDb,
      databases: req.session.databases || [],
      user: req.session.credentials.user,
      host: req.session.credentials.host,
    });
  } catch (err) {
    return res.status(500).send(err.message);
  } finally {
    pool?.end?.();
  }
});

app.get('/table/:schema/:table/structure', requireAuth, async (req, res) => {
  const pool = getPool(req);
  if (!pool) return res.redirect('/login');
  const { schema, table } = req.params;
  try {
    const columns = await tableStructure(pool, schema, table);
    return render(res, 'table-structure', {
      title: `Structure ${schema}.${table}`,
      schema,
      tableName: table,
      columns,
      currentDb: req.session.currentDb,
      databases: req.session.databases || [],
      user: req.session.credentials.user,
      host: req.session.credentials.host,
    });
  } catch (err) {
    return res.status(500).send(err.message);
  } finally {
    pool?.end?.();
  }
});

app.get('/query', requireAuth, (req, res) => {
  const sql = req.query.table ? `SELECT * FROM ${req.query.table} LIMIT 100` : '';
  return render(res, 'query', {
    title: 'SQL Query',
    sql,
    currentDb: req.session.currentDb,
    databases: req.session.databases || [],
    user: req.session.credentials.user,
    host: req.session.credentials.host,
  });
});

app.post('/query', requireAuth, async (req, res) => {
  const pool = getPool(req);
  if (!pool) return res.redirect('/login');
  const sql = (req.body.sql || '').trim();
  if (!sql) return res.redirect('/query');
  const limit = Math.min(parseInt(req.body.limit, 10) || 500, MAX_PAGE_SIZE);
  const offset = Math.max(0, parseInt(req.body.offset, 10) || 0);

  const isSelect = /^\s*SELECT\s+/i.test(sql);
  try {
    if (isSelect) {
      const hasLimit = /\bLIMIT\s+\d+/i.test(sql);
      const q = hasLimit ? sql : `${sql.replace(/;\s*$/, '')} LIMIT ${limit} OFFSET ${offset}`;
      const result = await pool.query(q);
      const total = result.rows.length;
      return render(res, 'query', {
        title: 'SQL Query',
        sql,
        currentDb: req.session.currentDb,
        databases: req.session.databases || [],
        user: req.session.credentials.user,
        host: req.session.credentials.host,
        result: {
          rows: result.rows,
          fields: result.fields,
          rowCount: result.rowCount,
          total,
          limit,
          offset,
        },
      });
    } else {
      const result = await pool.query(sql);
      const message = result.rowCount != null ? `Command executed. Rows affected: ${result.rowCount}` : 'Command executed.';
      return render(res, 'query', {
        title: 'SQL Query',
        sql,
        currentDb: req.session.currentDb,
        databases: req.session.databases || [],
        user: req.session.credentials.user,
        host: req.session.credentials.host,
        message,
      });
    }
  } catch (err) {
    return render(res, 'query', {
      title: 'SQL Query',
      sql,
      currentDb: req.session.currentDb,
      databases: req.session.databases || [],
      user: req.session.credentials.user,
      host: req.session.credentials.host,
      error: err.message,
    });
  } finally {
    pool?.end?.();
  }
});

// Keep one pool per request; we're ending it after each request. For dashboard we could cache pool per session - but then we need to handle pool.end() on logout and db switch. Simpler to create per-request and end. For high traffic, consider reusing pool per session.

app.listen(PORT, () => {
  console.log(`PG Admin listening on http://localhost:${PORT}`);
});
