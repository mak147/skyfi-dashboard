# SkyFi API

PHP modular monolith REST API for the SkyFi ISP Management System.

## Architecture

- PHP 8.2+ namespaces under `SkyFi\` (`src/`)
- Thin controllers → services → PDO repositories
- Module folders: Customers, Billing, Payments, Finance, MikroTik, PPPoE, Hotspot, Infrastructure, Monitoring, Support, Inventory, Purchasing, Vendors, FieldService, Reports, System, Notifications, Audit, Backup, Integration, Workflow, Portal, Rbac, Dashboard, Shared
- JSON:API-inspired envelopes via `Shared\Http\ApiResponse`
- HS256 access JWTs + HttpOnly rotated refresh cookies
- SQL migrations in `database/migrations/`

## Local setup

Prefer the monorepo Docker Compose workflow documented in [`docs/developer/10-LOCAL_DEVELOPMENT.md`](../docs/developer/10-LOCAL_DEVELOPMENT.md).

Native (optional):

```bash
composer install
cp ../.env.example .env   # configure DB_DSN, JWT_SECRET, etc.
php database/migrate.php
php database/seed.php
php -S localhost:8080 -t public public/index.php
```

## Tests

```bash
composer test
# or
./vendor/bin/phpunit
```

## Documentation

| Topic | Document |
| --- | --- |
| Architecture | [`docs/developer/01-ARCHITECTURE_GUIDE.md`](../docs/developer/01-ARCHITECTURE_GUIDE.md) |
| API reference | [`docs/developer/02-API_REFERENCE.md`](../docs/developer/02-API_REFERENCE.md) |
| Full route catalog | [`docs/developer/02-API_ROUTE_CATALOG.md`](../docs/developer/02-API_ROUTE_CATALOG.md) |
| Database | [`docs/developer/03-DATABASE_DOCUMENTATION.md`](../docs/developer/03-DATABASE_DOCUMENTATION.md) |
| Modules | [`docs/developer/05-MODULE_DOCUMENTATION.md`](../docs/developer/05-MODULE_DOCUMENTATION.md) |
| Coding standards | [`docs/developer/07-CODING_STANDARDS.md`](../docs/developer/07-CODING_STANDARDS.md) |
| Deployment | [`docs/deployment/DEPLOYMENT_GUIDE.md`](../docs/deployment/DEPLOYMENT_GUIDE.md) |

## Security notes

- Never log passwords, RouterOS credentials, access/refresh tokens, or raw payment secrets.
- Use HTTPS and `REFRESH_COOKIE_SECURE=true` outside local development.
- Encrypt MikroTik passwords with `MIKROTIK_CREDENTIAL_ENCRYPTION_KEY` (base64-encoded 32-byte key).
