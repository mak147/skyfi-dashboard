# SkyFi ISP Management System — Upgrade Guide v1.0.0

**Audience:** DevOps engineers, system administrators, release managers  
**Scope:** Procedures for upgrading a SkyFi deployment to v1.0.0 and future releases  
**Prerequisite:** Docker Compose production deployment (see `docs/deployment/DEPLOYMENT_GUIDE.md`)  

---

## 1. Overview

Since v1.0.0 is the first production release, there are no prior versions to upgrade from. This guide establishes the upgrade framework that will be used for all future releases, and documents the v1.0.0 fresh installation as the baseline.

---

## 2. Upgrade principles

1. **Backward compatibility first** — New releases must not break existing API contracts, database schemas, or client integrations.
2. **Additive migrations** — Database changes are always additive (new tables/columns/indexes). Destructive changes require explicit ops review.
3. **Zero-downtime goal** — Where possible, upgrades should not require service interruption.
4. **Rollback preparedness** — Every upgrade must have a documented rollback path.
5. **Test before deploy** — Always validate in a staging environment before applying to production.

---

## 3. Fresh installation (v1.0.0 baseline)

```bash
# 1. Clone the repository at the v1.0.0 tag
git clone --branch v1.0.0 --depth 1 https://github.com/mak147/skyfi-dashboard.git
cd skyfi-dashboard

# 2. Configure environment
cp docker/env/production.env.example .env
# Edit .env — replace ALL placeholder values with production secrets

# 3. Build and start
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d

# 4. Wait for services to be healthy
docker compose -f docker-compose.prod.yml ps

# 5. Apply database migrations
docker compose -f docker-compose.prod.yml exec backend php database/migrate.php --pretend
docker compose -f docker-compose.prod.yml exec backend php database/migrate.php

# 6. Seed RBAC baseline + initial administrator
docker compose -f docker-compose.prod.yml exec \
  -e SEED_ADMIN_EMAIL=admin@yourdomain.com \
  -e SEED_ADMIN_PASSWORD='Your-Secure-Password-Here!' \
  backend php database/seed.php

# 7. Verify
curl -fsS http://localhost/healthz
curl -fsS http://localhost/readyz
```

---

## 4. Standard upgrade procedure (for future releases)

### 4.1 Pre-upgrade checklist

- [ ] Review release notes for breaking changes and migration notes
- [ ] Verify staging environment passes all smoke tests with the new version
- [ ] Ensure database backup is current and verified
- [ ] Confirm `.env` has all new required variables (if any)
- [ ] Schedule upgrade window (if required)
- [ ] Notify stakeholders of planned upgrade

### 4.2 Backup

```bash
# Database logical backup
mkdir -p backups/mariadb
docker compose -f docker-compose.prod.yml exec mariadb \
  mariadb-dump -uroot -p"$MARIADB_ROOT_PASSWORD" \
  --single-transaction --routines --triggers skyfi \
  > backups/mariadb/skyfi-pre-upgrade-$(date +%Y%m%d%H%M%S).sql

# Verify backup is restorable (on a separate test instance)
# cat backups/mariadb/skyfi-pre-upgrade-*.sql | mariadb -uroot -p skyfi_test
```

### 4.3 Pull and build new images

```bash
# Option A: Pull from GHCR (if using published images)
docker compose -f docker-compose.prod.yml pull

# Option B: Build from source at new tag
git fetch --tags
git checkout v1.x.x
docker compose -f docker-compose.prod.yml build
```

### 4.4 Apply database migrations

```bash
# Always preview first
docker compose -f docker-compose.prod.yml exec backend php database/migrate.php --pretend

# Apply
docker compose -f docker-compose.prod.yml exec backend php database/migrate.php
```

### 4.5 Deploy new containers

```bash
# Rolling update with zero downtime
docker compose -f docker-compose.prod.yml up -d --no-deps --build backend
docker compose -f docker-compose.prod.yml up -d --no-deps --build supervisor
docker compose -f docker-compose.prod.yml up -d --no-deps --build nginx
```

### 4.6 Post-upgrade validation

```bash
# Health checks
curl -fsS http://localhost/healthz
curl -fsS http://localhost/readyz

# Smoke test critical paths:
# 1. Login as administrator
# 2. Verify RBAC permission checks
# 3. Create/view an invoice
# 4. Record a payment
# 5. Check finance dashboard
# 6. Access customer portal
# 7. Verify workflow automation page loads

# Check logs for errors
docker compose -f docker-compose.prod.yml logs --tail=100 backend
docker compose -f docker-compose.prod.yml logs --tail=100 nginx
```

### 4.7 Seed updates (if applicable)

If the new release adds permissions or roles:

```bash
# Re-running the seeder is safe (idempotent)
docker compose -f docker-compose.prod.yml exec backend php database/seed.php
```

---

## 5. Rollback procedure

If the upgrade fails or critical issues are discovered:

### 5.1 Immediate rollback (before migration)

If the issue is detected before database migrations are applied:

```bash
# Stop the new version
docker compose -f docker-compose.prod.yml down

# Start the previous version images
# (set previous image tags in .env or checkout previous tag)
docker compose -f docker-compose.prod.yml up -d
```

### 5.2 Rollback after migration

If database migrations were already applied:

1. **Stop the current stack:**
   ```bash
   docker compose -f docker-compose.prod.yml down
   ```

2. **Restore the database from the pre-upgrade backup:**
   ```bash
   # Drop and re-create (WARNING: destroys current data)
   docker compose -f docker-compose.prod.yml exec mariadb \
     mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" \
     -e "DROP DATABASE skyfi; CREATE DATABASE skyfi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   
   # Restore from backup
   cat backups/mariadb/skyfi-pre-upgrade-*.sql | \
     docker compose -f docker-compose.prod.yml exec -T mariadb \
       mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" skyfi
   ```

3. **Start the previous version:**
   ```bash
   # Checkout or configure previous image tags
   docker compose -f docker-compose.prod.yml up -d
   ```

4. **Verify rollback:**
   ```bash
   curl -fsS http://localhost/healthz
   curl -fsS http://localhost/readyz
   ```

---

## 6. Environment variable changes

### v1.0.0 required variables

All variables listed in `docker/env/production.env.example` are required for production deployment. Key variables that must be explicitly set:

| Variable | Purpose | Generation |
| --- | --- | --- |
| `JWT_SECRET` | HS256 JWT signing key | `openssl rand -base64 48` |
| `MIKROTIK_CREDENTIAL_ENCRYPTION_KEY` | Router credential encryption (32 bytes, base64) | `php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"` |
| `MARIADB_ROOT_PASSWORD` | Database root password | Random 32+ characters |
| `MARIADB_PASSWORD` | Application database user password | Random 32+ characters |
| `REDIS_PASSWORD` | Redis authentication password | Random 32+ characters |
| `APP_URL` | Public API URL | `https://api.yourdomain.com` |
| `APP_ISSUER` | JWT issuer claim | Same as APP_URL |
| `APP_AUDIENCE` | JWT audience claim | `https://app.yourdomain.com` |
| `CORS_ALLOWED_ORIGINS` | Allowed frontend origins | `https://app.yourdomain.com` |

**Never deploy with placeholder or default secrets in production.**

---

## 7. Version-specific notes

### v1.0.0

- First production release — no upgrade path from prior versions.
- All 33 migrations must be applied from scratch.
- The seeder must be run to initialize RBAC data and the initial administrator.

### Future releases

Each release will include:
- A `MIGRATION_GUIDE.md` section for version-specific migration steps
- An `UPGRADE_GUIDE.md` section for version-specific upgrade procedures
- New environment variables will be documented in the release notes
- Destructive schema changes will be flagged with explicit migration notes

---

## 8. Blue-green deployment (advanced)

For production environments requiring zero-downtime upgrades:

1. Deploy the new version on a separate set of containers (green) alongside the current version (blue).
2. Run database migrations — they must be backward-compatible with the blue version.
3. Switch traffic from blue to green at the load balancer level.
4. Monitor for errors; if issues arise, switch back to blue.
5. Once stable, tear down the blue environment.

**Requirements:**
- Database migrations must be additive only (no dropping columns that the old version reads).
- API contracts must remain backward-compatible during the transition window.
- Both versions must be able to run against the same database schema simultaneously.
