# SkyFi Production Deployment Guide

## Scope

This guide covers the Phase 3 deployment toolkit for the SkyFi ISP Management System. It packages the existing PHP API, React frontend, MariaDB, Redis, Nginx, and Supervisor runtime without changing business functionality.

## Deployment architecture

| Layer | Container | Purpose |
| --- | --- | --- |
| Edge / web | `nginx` | Serves the built React application, proxies `/api/*` to PHP-FPM, exposes `/healthz` and `/readyz`. |
| API runtime | `backend` | PHP 8.3 FPM runtime for the existing REST API. |
| Process supervisor | `supervisor` | Runs supervised operational worker processes and provides a place for future scheduled operational commands. |
| Database | `mariadb` | MariaDB 11.4 persistent application database. |
| Cache / coordination | `redis` | Redis 7.4 with append-only persistence and production password support. |

The default production entrypoint is `docker-compose.prod.yml`. Local development uses `docker-compose.yml`.

## Prerequisites

- Docker Engine 25+
- Docker Compose plugin 2.24+
- 2 CPU cores minimum for a small deployment
- 4 GB RAM minimum for a small deployment
- A DNS name for the frontend/API host
- A TLS terminator in front of the Compose stack, or a platform load balancer that terminates HTTPS

## Environment setup

1. Copy the production template:

   ```bash
   cp docker/env/production.env.example .env
   ```

2. Replace every placeholder secret:

   ```bash
   # JWT secret: at least 32 random bytes
   openssl rand -base64 48

   # MikroTik credential encryption key: exactly 32 bytes, base64 encoded
   php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
   ```

3. Set public URLs:

   ```env
   APP_URL=https://api.example.com
   APP_ISSUER=https://api.example.com
   APP_AUDIENCE=https://app.example.com
   CORS_ALLOWED_ORIGINS=https://app.example.com
   VITE_API_BASE_URL=/api/v1
   ```

4. Size MariaDB and PHP-FPM for the server:

   ```env
   MARIADB_INNODB_BUFFER_POOL_SIZE=1G
   PHP_FPM_MAX_CHILDREN=40
   ```

## Build and start production

```bash
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d
```

Check status:

```bash
docker compose -f docker-compose.prod.yml ps
curl -fsS http://localhost/healthz
curl -fsS http://localhost/readyz
```

## Database migration and seed commands

Run migrations after the database is healthy:

```bash
docker compose -f docker-compose.prod.yml exec backend php database/migrate.php --pretend
docker compose -f docker-compose.prod.yml exec backend php database/migrate.php
```

Seed authentication roles and permissions only when required by the target environment:

```bash
docker compose -f docker-compose.prod.yml exec \
  -e SEED_ADMIN_EMAIL=admin@example.com \
  -e SEED_ADMIN_PASSWORD='replace-with-secure-password' \
  backend php database/seed.php
```

## Health and readiness checks

- `/healthz` verifies that the API runtime is reachable.
- `/readyz` verifies database connectivity and writable application storage.

Use `/healthz` for load balancer liveness checks and `/readyz` for readiness checks during deployment rollouts.

## Local development environment

```bash
cp docker/env/development.env.example .env
docker compose up -d --build
```

Development URLs:

- Application: <http://localhost:8080>
- Vite dev server: <http://localhost:5173>
- API base path through Nginx: <http://localhost:8080/api/v1>

Useful commands:

```bash
docker compose exec backend php database/migrate.php
docker compose exec backend php database/seed.php
docker compose logs -f backend nginx frontend
docker compose down
```

## Persistent volumes

| Volume | Contents |
| --- | --- |
| `mariadb-data` | MariaDB data directory. |
| `redis-data` | Redis append-only data. |
| `backend-storage` | API logs, cache, and uploaded runtime files. |

Do not delete these volumes during upgrades unless a restore has been verified.

## Backup notes

The Compose file mounts `./backups/mariadb` into the MariaDB container. Example logical backup:

```bash
mkdir -p backups/mariadb
docker compose -f docker-compose.prod.yml exec mariadb \
  mariadb-dump -uroot -p"$MARIADB_ROOT_PASSWORD" --single-transaction --routines --triggers skyfi \
  > backups/mariadb/skyfi-$(date +%Y%m%d%H%M%S).sql
```

For production, run backups from an external scheduler and copy artifacts to off-site storage.

## Upgrade procedure

1. Review release notes and migration notes.
2. Back up MariaDB and persistent volumes.
3. Pull or build the new images.
4. Run migrations with `--pretend` first.
5. Apply migrations.
6. Restart services:

   ```bash
   docker compose -f docker-compose.prod.yml up -d --build
   ```

7. Verify `/healthz`, `/readyz`, login, RBAC, billing, finance, customer portal, and workflow smoke paths.

## Rollback procedure

1. Stop the current stack:

   ```bash
   docker compose -f docker-compose.prod.yml down
   ```

2. Restore the previous image tags in `.env` or check out the previous release.
3. Restore MariaDB from the verified backup if migrations were already applied.
4. Start the previous version:

   ```bash
   docker compose -f docker-compose.prod.yml up -d
   ```

## Security checklist

- Keep `.env` out of version control.
- Use high-entropy `JWT_SECRET` and MikroTik encryption keys.
- Set `REFRESH_COOKIE_SECURE=true` in production.
- Terminate TLS before traffic reaches Nginx.
- Restrict direct database and Redis access to the Docker network.
- Rotate database, Redis, and admin passwords during handover.
- Do not expose the Vite development service in production.

## Troubleshooting

```bash
# Container status
docker compose -f docker-compose.prod.yml ps

# API logs
docker compose -f docker-compose.prod.yml logs -f backend

# Nginx logs
docker compose -f docker-compose.prod.yml logs -f nginx

# Database health
docker compose -f docker-compose.prod.yml exec mariadb mariadb-admin ping -uroot -p"$MARIADB_ROOT_PASSWORD"

# Readiness details
curl -s http://localhost/readyz | jq
```
