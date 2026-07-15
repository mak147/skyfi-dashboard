# Deployment Guide (Developer Index)

Phase 3 delivered the production deployment toolkit. **Do not duplicate operational runbooks here**—this page points developers at the authoritative deployment artifacts and summarizes the developer-facing workflow.

## 1. Authoritative documents

| Document | Path |
| --- | --- |
| Production deployment guide | [`docs/deployment/DEPLOYMENT_GUIDE.md`](../deployment/DEPLOYMENT_GUIDE.md) |
| CI/CD documentation | [`docs/deployment/CI_CD.md`](../deployment/CI_CD.md) |
| CI workflow template | [`docs/deployment/github-actions/ci.yml`](../deployment/github-actions/ci.yml) |
| CD workflow template | [`docs/deployment/github-actions/cd.yml`](../deployment/github-actions/cd.yml) |
| Production Compose | [`docker-compose.prod.yml`](../../docker-compose.prod.yml) |
| Development Compose | [`docker-compose.yml`](../../docker-compose.yml) |
| Env templates | [`docker/env/`](../../docker/env/) |

## 2. Runtime topology (production)

```text
Internet → TLS terminator → nginx
                              ├─ static SPA assets
                              ├─ /api/*  → php-fpm (backend)
                              ├─ /healthz
                              └─ /readyz → readiness (DB + storage)
           supervisor → operational workers
           mariadb    → system of record
           redis      → cache / coordination
```

## 3. Developer deploy smoke path

After a release candidate is built:

```bash
cp docker/env/production.env.example .env
# fill secrets and URLs

docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d

docker compose -f docker-compose.prod.yml exec backend php database/migrate.php --pretend
docker compose -f docker-compose.prod.yml exec backend php database/migrate.php

curl -fsS http://localhost/healthz
curl -fsS http://localhost/readyz
```

Smoke checklist:

1. Login (staff)
2. RBAC permissions load
3. Customers list
4. Invoice list + detail
5. Finance dashboard
6. Customer portal login path
7. Workflow list

## 4. CI expectations for contributors

When the CI template is installed as `.github/workflows/ci.yml`, pull requests should pass:

- PHP syntax + PHPUnit
- ESLint + Vitest + production frontend build
- Compose config validation
- Image builds (backend, frontend/nginx as configured)

Fix CI failures on your branch before requesting review.

## 5. CD expectations

CD is **manual** (`workflow_dispatch`) in the provided template:

1. Choose `image_tag`
2. Optionally publish images to GHCR
3. Download deployment bundle artifact
4. Deploy on the target host with production Compose + secrets

Runtime secrets never belong in GitHub workflow files.

## 6. Upgrade sketch (developers)

1. Read release notes / migration notes for the version.
2. Back up MariaDB and volumes.
3. Deploy new images.
4. `migrate.php --pretend` then `migrate.php`.
5. Verify health/ready and smoke paths.
6. Roll back images + restore DB if migrations already applied and a defect is critical.

Full procedures: `docs/deployment/DEPLOYMENT_GUIDE.md`. Operations deep-dives (monitoring, DR, incident response) are Phase 5 deliverables under a future `docs/operations/` set.

## 7. Local vs production differences

| Concern | Local | Production |
| --- | --- | --- |
| Compose file | `docker-compose.yml` | `docker-compose.prod.yml` |
| `APP_DEBUG` | often true | false |
| `REFRESH_COOKIE_SECURE` | false | true |
| TLS | usually none | required at edge |
| Frontend | Vite dev service | Built static assets via Nginx |
| Secrets | local `.env` | host secret manager / hardened `.env` |
