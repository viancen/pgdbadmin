# PG Admin — PostgreSQL 18+ Admin Dashboard

A standalone, phpMyAdmin-style admin dashboard for **PostgreSQL 18+** with a modern Tailwind UI. Login with **host**, **user**, and **password**; switch between multiple databases; browse tables with pagination; view structure; run SQL.

Optimized for **performance** (pagination, connection pooling per request) and ready to deploy on **Laravel Forge** (Node.js site, not a Laravel app).

## Features

- **Login**: Host, port, user, password, optional SSL
- **Database switcher**: Select or switch database when multiple are available
- **Tables list**: All tables in the current database with schema badges
- **Browse table**: Paginated rows (configurable page size, max 500)
- **Table structure**: Column names, types, nullability, defaults
- **SQL runner**: Execute any PostgreSQL (SELECT/INSERT/UPDATE/DELETE); SELECT results limited to 500 rows
- **Session-based auth**: Credentials stored server-side; CSRF protection

## Requirements

- Node.js 18+
- PostgreSQL 18+ (or compatible; tested with 18+)

## Quick start

```bash
cp .env.example .env
# Edit .env: set PORT, SESSION_SECRET

npm install
npm run build:css
npm start
```

Open `http://localhost:3000`, log in with your PostgreSQL host, user, and password, then pick a database from the dropdown.

## Deploy on Laravel Forge

1. **Create a Node.js site** (not a Laravel app):
   - In Forge: create a new site and set the web directory to your app root (e.g. `pgdbadmin` or the repo path).
   - Enable **Node.js** for this site and set **Node version** to 18+.

2. **Deploy the app** (e.g. clone repo or upload files):
   - In the app directory run:
     - `npm install --production`
     - `npm run build:css`
   - Set **Start Command** (in Forge’s Daemons or Process Manager / ecosystem) to:
     - `node server.js`
     - Or: `npm start` (if your `package.json` has `"start": "node server.js"`).

3. **Environment**:
   - In Forge’s **Environment** (or `.env` on the server), set:
     - `PORT` to the port your Node app listens on (e.g. `3000` if you’re proxying to it).
     - `NODE_ENV=production`
     - `SESSION_SECRET` to a long random string.

4. **Nginx proxy** (if the site is behind Nginx):
   - Add a **Proxy Pass** (or location block) to forward to `http://127.0.0.1:PORT` (e.g. `http://127.0.0.1:3000`).
   - Or use Forge’s “Websites” → your site → “Proxy” to point to the Node app port.

5. **Process manager** (recommended):
   - Use a Daemon or PM2 so the Node process restarts on failure:
     - Command: `node server.js` (or `npm start`), working directory = app root.
   - Or in Forge use “Daemons” with the same command and directory.

6. **Security**:
   - Use HTTPS (Forge can provision SSL).
   - Restrict access (e.g. IP allowlist or Forge’s “Restrict by IP”) so only admins can reach the dashboard.

## Environment variables

| Variable         | Description                          | Default        |
|------------------|--------------------------------------|----------------|
| `PORT`           | Server port                          | `3000`         |
| `NODE_ENV`       | `development` / `production`         | `development`  |
| `SESSION_SECRET` | Secret for session signing (required in prod) | (dev fallback) |
| `PG_DEFAULT_PORT`| Not used by app; doc only            | `5432`         |

## Development

```bash
npm install
npm run build:css   # Build Tailwind once
npm start           # Run server
```

After changing Tailwind or `public/css/input.css`, run `npm run build:css` again. For live CSS rebuilds you can use `npx tailwindcss -i ./public/css/input.css -o ./public/css/output.css --watch` in a separate terminal.

## License

MIT
