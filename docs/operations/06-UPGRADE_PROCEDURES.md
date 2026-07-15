# Operations Guide 06 — Upgrade & Rollback Procedures

**Phase:** 5 — Operations Documentation  
**Audience:** Release Engineers, DevOps, SRE, Quality Assurance (QA) Lead  
**Status:** Production-Ready Standard  
**Last reviewed:** 2026-07-15

---

## 1.0 Document Scope & Goals

This document specifies the protocols and commands required to upgrade or roll back the SkyFi ISP Management System in production.

Our core deployment goal is to achieve **near-zero-downtime releases** while ensuring 100% data integrity. To accomplish this, all schema changes must maintain backward compatibility, and release procedures must strictly utilize automated database pre-checks.

---

## 2.0 Pre-Upgrade Pre-flight Checklist

At least 24 hours before a production deployment, the following steps must be completed:

- [ ] **QA Sign-off:** The specific build (represented by a Git Commit SHA) must have passed all tests (PHPUnit, Vitest, Playwright E2E) with formal QA approval.
- [ ] **Release Notes Ready:** A detailed changelog highlighting new schemas and configuration flags must be prepared.
- [ ] **Maintenance Window Communicated:** If the release requires a transient API pause, schedule a maintenance window and notify support staff and NOC.
- [ ] **Database Pre-Check:** Verify that there is at least 15% free disk space on the MariaDB volume before launching migrations.

---

## 3.0 Step-by-Step Production Upgrade Sequence

Deployments are executed directly on the production host using the standard Docker Compose production entrypoint.

```
       [ Draft & Verify Releases ]
                   |
                   v
         [ Pull Docker Images ]
                   |
                   v
       [ SQL Dry-Run Check (--pretend) ]
                   |
                   v
      [ Execute Automated Migrations ]
                   |
                   v
  [ Blue-Green Container Swap & Restart ]
                   |
                   v
        [ Run Smoke Tests ]
```

### Step 1: Secure an Active Pre-Deployment Backup

Always create a fresh database snapshot prior to making any system changes.

```bash
mkdir -p backups/pre_deploy
DB_ROOT_PASSWORD=$(grep MARIADB_ROOT_PASSWORD .env | cut -d '=' -f2)

docker compose -f docker-compose.prod.yml exec -t mariadb \
  mariadb-dump -uroot -p"${DB_ROOT_PASSWORD}" --single-transaction --routines --triggers skyfi \
  > backups/pre_deploy/skyfi_predeploy_$(date +%Y%m%d_%H%M%S).sql
```

### Step 2: Fetch Modern Docker Images

Pull the approved container images from the Amazon ECR or GitHub container registry (configured in `.env` or defined by tags):

```bash
docker compose -f docker-compose.prod.yml pull
```

### Step 3: Run Database Dry-Run Schema Verification

Our custom PHP migrator supports dry-run checking using the `--pretend` flag. This prints the SQL queries to standard output *without* executing them. This allows Database Administrators to review the precise impact on the database engine.

```bash
docker compose -f docker-compose.prod.yml exec backend \
  php database/migrate.php --pretend
```

Review the printed SQL output. Look for:
*   `ALTER TABLE` operations on large transactional datasets (e.g., `billing_invoices`, `audit_logs`).
*   Verify that any `ADD COLUMN` declarations provide default values to prevent null pointer exceptions in older application containers still handling requests.

### Step 4: Execute Live Migrations

If the dry-run check passes, execute the database migrations:

```bash
docker compose -f docker-compose.prod.yml exec backend \
  php database/migrate.php
```

### Step 5: Perform Graceful Application Restart

To update active user traffic without dropping in-flight TCP connections:

```bash
# 1. Start up the new containers
docker compose -f docker-compose.prod.yml up -d --build --no-recreate

# 2. Trigger a graceful reload of the Nginx config
docker compose -f docker-compose.prod.yml exec nginx nginx -s reload

# 3. Gracefully reload PHP-FPM workers (kills idle workers, lets active finish processing)
docker compose -f docker-compose.prod.yml exec backend kill -USR2 1

# 4. Restart the Supervisor background daemon to pick up the latest code tags
docker compose -f docker-compose.prod.yml exec supervisor supervisorctl restart all
```

---

## 4.0 Post-Upgrade Smoke Validation

Immediately following container reloads, verify the health of the live system:

1.  **Query Health & Readiness Handshakes:**
    ```bash
    curl -fsS http://localhost/healthz
    curl -fsS http://localhost/readyz
    ```
2.  **Verify DB Migration Tracking Table:**
    Ensure that the migrations have been logged successfully inside the database:
    ```bash
    docker compose -f docker-compose.prod.yml exec mariadb \
      mariadb -uroot -p -e "SELECT * FROM migrations ORDER BY migrated_at DESC LIMIT 5;" skyfi
    ```
3.  **Validate Active Sessions:**
    Browse the active administrator panel and verify navigation across core billing, connections, and monitoring features.

---

## 5.0 Immediate Rollback Triggers

Initiate an immediate rollback if any of the following occur during the 30 minutes post-deployment:

*   The readiness check `/readyz` fails continuously for over 3 minutes.
*   The API HTTP 5xx error rate spikes above 2% of total traffic.
*   The MariaDB CPU utilization spikes to 100% and remains locked for over 5 minutes.
*   Core business workflows (e.g., invoice generation, PPPoE router authentication) fail to execute.

---

## 6.0 Safe Rollback Procedures

If a rollback is triggered, execute the following steps to return to a stable state:

```
          [ Identify Outage Trigger ]
                      |
                      v
      [ Down the Current Broken Stack ]
                      |
                      v
      [ Revert Codebase / Git Commit ]
                      |
                      v
      [ Revert Database to Safe State ]
                      |
                      v
         [ Restart Stable Container ]
```

### Step 1: Revert Code & Env Tags

1.  Stop the active container stack:
    ```bash
    docker compose -f docker-compose.prod.yml down
    ```
2.  Checkout the previous stable release tag:
    ```bash
    git checkout tags/v1.0.4 -b release-rollback
    ```
3.  Ensure `.env` matches the configuration file associated with the reverted version.

### Step 2: Resolve Database Rollback Complexity

Database rollback procedures depend on whether the failed migration changed schemas.

#### Option A: Minor Schema / Backward-Compatible (No DB Reversion Needed)
If the failed migration only added new tables or optional columns, the older application code will ignore these columns. Simply restart the older containers.

#### Option B: Structural Schema Change (Restore Required)
If the migration deleted columns, split tables, or altered indexes in a way that is incompatible with the older code, you must restore the database state from the pre-deployment backup taken in Section 3.0 Step 1.

```bash
# 1. Wipe the current database structures
docker compose -f docker-compose.prod.yml exec -T mariadb \
  mariadb -uroot -p"${DB_ROOT_PASSWORD}" -e "
    DROP DATABASE skyfi;
    CREATE DATABASE skyfi;
  "

# 2. Import the pre-deployment sql dump
docker compose -f docker-compose.prod.yml exec -T mariadb \
  mariadb -uroot -p"${DB_ROOT_PASSWORD}" skyfi < backups/pre_deploy/skyfi_predeploy_[TIMESTAMP].sql
```

### Step 3: Boot Up the Stable Stack

```bash
# 1. Spin up the older container code
docker compose -f docker-compose.prod.yml up -d --build

# 2. Flush Redis caching layer
docker compose -f docker-compose.prod.yml exec redis redis-cli flushall

# 3. Verify health
curl -fsS http://localhost/healthz
curl -fsS http://localhost/readyz
```
