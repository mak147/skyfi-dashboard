# Local Development Guide

## 1. Recommended approach: Docker Compose

This is the supported path and matches CI/production topology most closely.

### 1.1 First-time setup

```bash
# From repository root
cp docker/env/development.env.example .env

# Optional: align root template
# cp .env.example .env

docker compose up -d --build
```

Wait until containers are healthy:

```bash
docker compose ps
curl -fsS http://localhost/healthz || curl -fsS http://localhost:8080/healthz
curl -fsS http://localhost:8080/readyz
```

### 1.2 Database migrate and seed

```bash
docker compose exec backend php database/migrate.php --pretend
docker compose exec backend php database/migrate.php

# Seed roles/permissions/admin (set SEED_* in env if required)
docker compose exec backend php database/seed.php
```

### 1.3 URLs

| Surface | URL |
| --- | --- |
| App via Nginx | http://localhost:8080 |
| Vite HMR (dev frontend service) | http://localhost:5173 |
| API base | http://localhost:8080/api/v1 |
| Health | http://localhost:8080/healthz |
| Ready | http://localhost:8080/readyz |
| MariaDB (host) | localhost:3306 (if published) |
| Redis (host) | localhost:6379 (if published) |

Port overrides: `SKYFI_HTTP_PORT`, `SKYFI_FRONTEND_PORT`, `SKYFI_DB_PORT`, `SKYFI_REDIS_PORT`.

### 1.4 Hot reload layout

Development Compose mounts backend source and frontend source:

- PHP code changes under `backend/src` are visible to FPM without rebuilding the image (restart if opcache is aggressive).
- Frontend Vite service hot-reloads TSX changes.
- Nginx proxies `/api/*` to PHP-FPM and can proxy the SPA depending on `docker/nginx/dev.conf`.

### 1.5 Useful Compose commands

```bash
docker compose logs -f backend nginx frontend
docker compose exec backend sh
docker compose exec mariadb mariadb -uskyfi -p"$MARIADB_PASSWORD" skyfi
docker compose restart backend
docker compose down
docker compose down -v   # destroys DB volume — local data loss
```

## 2. Backend native (optional)

Use when iterating on PHP without containers.

```bash
cd backend
cp ../.env.example .env   # or docker env values pointed at local MariaDB
composer install
php database/migrate.php
php database/seed.php
php -S localhost:8080 -t public public/index.php
```

Requirements:

- PHP 8.2+ with PDO MySQL, JSON, mbstring, openssl
- MariaDB/MySQL reachable via `DB_DSN`
- Valid `JWT_SECRET` (≥32 chars)
- Optional: `MIKROTIK_CREDENTIAL_ENCRYPTION_KEY` (base64 32-byte key)

## 3. Frontend native (optional)

```bash
cd frontend
cp .env.example .env  # if present; else set VITE_API_BASE_URL
npm install
npm run dev
```

Point `VITE_API_BASE_URL` at the API, e.g. `http://localhost:8080/api/v1`. CORS must allow the Vite origin (`CORS_ALLOWED_ORIGINS`).

## 4. Environment variables (developer-critical)

| Variable | Purpose |
| --- | --- |
| `JWT_SECRET` | HS256 signing key for access tokens |
| `DB_DSN` / `DB_USERNAME` / `DB_PASSWORD` | PDO connection |
| `CORS_ALLOWED_ORIGINS` | Comma-separated browser origins |
| `REFRESH_COOKIE_SECURE` | `false` on local HTTP; `true` in HTTPS prod |
| `APP_ENV` / `APP_DEBUG` | Environment + error verbosity |
| `MIKROTIK_CREDENTIAL_ENCRYPTION_KEY` | Encrypts router passwords at rest |
| `VITE_API_BASE_URL` | SPA API prefix |
| `SEED_ADMIN_EMAIL` / `SEED_ADMIN_PASSWORD` | Initial admin seed |

Full templates: `docker/env/development.env.example`, `docker/env/production.env.example`, root `.env.example`.

## 5. Running tests

### Backend (PHPUnit)

```bash
cd backend
composer install
./vendor/bin/phpunit
# or
composer test
```

Suites configured in `phpunit.xml`: `Unit`, `Integration`, `Feature`.

### Frontend unit (Vitest)

```bash
cd frontend
npm install
npx vitest run
npx vitest run --coverage
```

### End-to-end (Playwright)

```bash
cd frontend
npx playwright install   # first time
npx playwright test
```

Ensure the app/API are reachable per `playwright.config.ts` base URL settings.

### Lint and production build

```bash
cd frontend
npm run lint
npm run build
```

## 6. Working on a module

Example: Customers list bug.

1. Reproduce in UI at `/customers`.
2. Find route in `backend/src/Customers/Routes/customers.php`.
3. Trace `CustomerController` → `CustomerService` → `PdoCustomerRepository`.
4. Add a failing PHPUnit test under `backend/tests/Unit/Customers`.
5. Fix service/repository.
6. Adjust frontend query/component under `frontend/src/features/customers` if needed.
7. Run unit tests + relevant Vitest/Playwright.

## 7. Database tips

```bash
# Pending migrations
docker compose exec backend php database/migrate.php --pretend

# Interactive SQL
docker compose exec mariadb mariadb -uskyfi -pskyfi_secret skyfi

# Export local dump
docker compose exec mariadb \
  mariadb-dump -uskyfi -pskyfi_secret skyfi > /tmp/skyfi-local.sql
```

Reset local DB:

```bash
docker compose down -v
docker compose up -d
docker compose exec backend php database/migrate.php
docker compose exec backend php database/seed.php
```

## 8. Troubleshooting

| Symptom | Checks |
| --- | --- |
| CORS errors in browser | `CORS_ALLOWED_ORIGINS` includes exact Vite/Nginx origin |
| Login succeeds but subsequent calls 401 | Cookie not sent (`withCredentials`), Secure cookie on HTTP, wrong API host |
| `/readyz` fails | MariaDB not healthy; storage volume permissions |
| Blank page | Frontend build/runtime error; check Vite logs |
| Migration fails mid-way | Inspect SQL error; fix forward with new migration if partial apply occurred |
| Permission denied in UI | User roles/permissions seed; `GET /api/v1/me/permissions` |
| MikroTik crypto errors | `MIKROTIK_CREDENTIAL_ENCRYPTION_KEY` must be base64 of 32 bytes |

Logs:

```bash
docker compose logs -f backend
# API file log path (in container)
docker compose exec backend tail -n 100 storage/logs/app.log
```

## 9. IDE run configurations (suggested)

- **API:** Docker Compose service `backend` attach, or native `php -S`
- **Web:** browser against `:8080`
- **Tests:** PHPUnit config `backend/phpunit.xml`; Vitest via npm script

## 10. Related guides

- [Deployment Guide](./11-DEPLOYMENT_GUIDE.md)
- [Contribution Guide](./08-CONTRIBUTION_GUIDE.md)
- Root `TESTING.md`
- `docs/deployment/DEPLOYMENT_GUIDE.md`
