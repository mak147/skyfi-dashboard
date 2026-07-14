# SkyFi API — Authentication Slice

This directory contains the PHP REST API for the SkyFi Networks ISP Management System. The current implementation intentionally contains **only the Shared Authentication/RBAC slice**. No Dashboard, Customers, Packages, Billing, Payments, Finance, Inventory, or Reports code is included yet.

## Architecture

- PHP 8.2+ modular monolith, with authentication in `src/Shared/Auth`.
- Thin HTTP controller → authentication service → repository layers.
- MySQL 8.x through PDO; all schema changes are versioned SQL migrations.
- Stateless HS256 access JWTs (15 minutes by default).
- Opaque, SHA-256-hashed, single-use refresh tokens in `refresh_tokens`.
- Refresh tokens are rotated and delivered only in `HttpOnly; Secure; SameSite=Strict` cookies.
- JSON:API-inspired resource and error envelopes.
- JSON structured logging with sensitive-value scrubbing.

## Local setup

1. Copy `.env.example` to `.env` and set a random `JWT_SECRET` of at least 32 characters.
2. Create the MySQL database and run `database/migrations/20260714000000_create_authentication_tables.sql`.
3. Install Composer dependencies: `composer install`.
4. Run the RBAC seeder through the application's migration/seed runner. An initial administrator should be supplied through secret-managed environment variables rather than committed credentials.
5. Serve the `public/` directory as the web root, for example: `php -S localhost:8080 -t public public/index.php`.

## Authentication endpoints

- `POST /api/v1/auth/login` — JSON `{ "email": "...", "password": "...", "rememberMe": true }`.
- `POST /api/v1/auth/refresh` — sends the HttpOnly refresh cookie and rotates it.
- `POST /api/v1/auth/logout` — revokes the refresh cookie and returns `204 No Content`.

A successful login/refresh returns a JSON:API resource with `data.attributes.accessToken` and a safe `data.attributes.user` object. The refresh token is never returned in JSON.

## Security notes

- Never log passwords, access tokens, refresh tokens, secrets, or raw payment data.
- Use HTTPS and set `REFRESH_COOKIE_SECURE=true` outside local development.
- The frontend keeps access tokens in memory only; the refresh token remains inaccessible to JavaScript.
