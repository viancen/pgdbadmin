# PG Admin — Laravel

PostgreSQL 18+ admin dashboard (phpMyAdmin-style) built with **Laravel**. Login with host/user/password; database switcher; tables list; browse table (paginated); table structure; SQL runner. Session-based auth and CSRF via Laravel.

## Requirements

- PHP 8.2+
- Composer
- PostgreSQL 18+ (or compatible) — the **target** DB you want to manage (not required for the app’s own data)
- Node 18+ (for building frontend assets)

The app uses SQLite by default for its own data (sessions, cache, jobs). You can keep that or switch to MySQL/PostgreSQL in `.env`.

## Quick start

```bash
cp .env.example .env
php artisan key:generate

# Optional: use file sessions so you don’t need to run migrations
# In .env set: SESSION_DRIVER=file

# If using SESSION_DRIVER=database (default), run migrations
php artisan migrate

npm install
npm run build

php artisan serve
```

Open `http://localhost:8000`, then log in with your PostgreSQL host, user, and password.

## Environment

- `APP_KEY` — required; set with `php artisan key:generate`.
- `SESSION_DRIVER` — `file` or `database`. If `database`, run `php artisan migrate`.
- `APP_URL` — used for links; set in production.

No PostgreSQL env vars are needed for the app itself; users provide connection details at login.

## Deploy (e.g. Laravel Forge)

1. Create a **Laravel** site (PHP), not a Node site.
2. Point the web root to the project’s `public` directory.
3. After deploy:
   - `composer install --no-dev`
   - `php artisan key:generate` (or set `APP_KEY` in env)
   - `php artisan migrate --force` (if using database sessions/cache)
   - `npm ci && npm run build`
4. Set `APP_ENV=production`, `APP_DEBUG=false`, and a proper `APP_URL`.
5. Use HTTPS and restrict access (e.g. IP allowlist).

## Routes

| Method | Path | Description |
|--------|------|-------------|
| GET | `/` | Landing (no auth) or dashboard (tables) |
| GET/POST | `/login` | Login form / submit |
| POST | `/logout` | Logout |
| GET | `/docs/connecting` | Connecting guide (NL) |
| POST | `/switch-db` | Change current database (auth) |
| GET | `/table/{schema}/{table}` | Browse table rows (auth) |
| GET | `/table/{schema}/{table}/structure` | Table structure (auth) |
| GET/POST | `/query` | SQL console (auth) |

## License

MIT.
