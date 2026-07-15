# SkyFi ISP Management System — Migration Guide v1.0.0

**Audience:** Database administrators, DevOps engineers, release managers  
**Scope:** Database migration procedures for the SkyFi v1.0.0 release  
**Prerequisite:** SkyFi has never been deployed to the target environment before (greenfield)  

---

## 1. Overview

SkyFi v1.0.0 ships with **33 ordered SQL migration files** that create the complete database schema. Since this is the first production release, there are no prior schemas to migrate from. This guide documents:

1. Greenfield schema installation
2. Seed data initialization (RBAC roles, permissions, admin user)
3. Migration runner usage and verification
4. Post-migration validation checklist

---

## 2. Migration files

All migrations live under `backend/database/migrations/` and are applied in filename order.

| # | Migration file | Domain |
| --- | --- | --- |
| 1 | `20260714000000_create_authentication_tables.sql` | Users, roles, permissions, role_user, permission_role, refresh_tokens |
| 2 | `20260714100000_create_audit_logs_table.sql` | Audit logs |
| 3 | `20260715000000_create_customers_table.sql` | Customers |
| 4 | `20260716000000_create_packages_tables.sql` | Packages, categories, pricing, profiles |
| 5 | `20260717000000_create_connections_table.sql` | Connections |
| 6 | `20260718000000_create_billing_tables.sql` | Invoices, items, schedules, late fee rules |
| 7 | `20260719000000_create_payments_tables.sql` | Payments, allocations, receipts, refunds |
| 8 | `20260720000000_create_finance_tables.sql` | Chart of accounts, journals, ledger, expenses |
| 9 | `20260721000000_create_mikrotik_tables.sql` | Routers, groups, tags, health snapshots |
| 10 | `20260722000000_create_pppoe_tables.sql` | PPPoE accounts, secrets, sessions |
| 11 | `20260723000000_create_pop_sites_table.sql` | POP sites |
| 12 | `20260724000000_create_towers_table.sql` | Towers |
| 13 | `20260725000000_create_sectors_table.sql` | Sectors |
| 14 | `20260726000000_create_network_devices_table.sql` | Network devices |
| 15 | `20260727000000_add_infrastructure_fks_to_connections.sql` | Infrastructure FK links |
| 16 | `2026_07_14_000001_create_hotspot_tables.sql` | Hotspot profiles, users, vouchers, sessions |
| 17 | `2026_07_28_000000_create_monitoring_tables.sql` | Monitoring, alerts, interface snapshots |
| 18 | `2026_07_29_000000_create_support_tables.sql` | Tickets, teams, SLA, comments |
| 19 | `2026_07_30_000000_create_inventory_tables.sql` | Products, warehouses, stock, assets |
| 20 | `2026_07_31_000000_create_purchasing_tables.sql` | Purchase requests, orders, goods receipts |
| 21 | `2026_08_01_000000_create_vendor_management_tables.sql` | Suppliers, contacts, contracts, quotations |
| 22 | `2026_08_02_000000_create_field_service_tables.sql` | Technicians, work orders, visits |
| 23 | `2026_08_03_000000_create_reports_tables.sql` | Report catalog, saved reports, schedules |
| 24 | `2026_08_04_000000_create_system_administration_tables.sql` | Company, branches, departments, settings |
| 25 | `2026_08_05_000000_create_notification_center_tables.sql` | Notifications, templates, delivery |
| 26 | `2026_08_06_000000_create_audit_compliance_tables.sql` | Compliance policies, retention |
| 27 | `2026_08_07_000000_create_backup_tables.sql` | Backup jobs, schedules, restore |
| 28 | `2026_08_08_000000_create_integration_tables.sql` | API keys, webhooks, connectors |
| 29 | `2026_08_09_000000_create_workflow_tables.sql` | Automation rules, executions |
| 30 | `2026_08_10_000000_add_customer_id_to_users.sql` | Customer portal link (users.customer_id) |
| 31 | `2026_08_10_000001_create_password_resets_table.sql` | Password reset tokens |
| 32 | `2026_08_11_000000_create_rate_limits_table.sql` | API rate limit counters |
| 33 | `2026_08_11_000001_add_performance_indexes.sql` | Performance indexes |

---

## 3. Greenfield installation

### 3.1 Docker (recommended)

```bash
# Ensure the stack is running and MariaDB is healthy
docker compose -f docker-compose.prod.yml up -d mariadb
docker compose -f docker-compose.prod.yml exec mariadb mariadb-admin ping \
  -h 127.0.0.1 -uroot -p"$MARIADB_ROOT_PASSWORD" --silent

# Dry-run: list pending migrations without executing
docker compose -f docker-compose.prod.yml exec backend php database/migrate.php --pretend

# Apply all migrations
docker compose -f docker-compose.prod.yml exec backend php database/migrate.php

# Seed RBAC baseline + initial administrator
docker compose -f docker-compose.prod.yml exec \
  -e SEED_ADMIN_EMAIL=admin@yourdomain.com \
  -e SEED_ADMIN_PASSWORD='Your-Secure-Password-Here!' \
  backend php database/seed.php
```

### 3.2 Native (bare metal)

```bash
# Ensure DB_DSN, DB_USERNAME, DB_PASSWORD are set in your environment
cd backend
php database/migrate.php --pretend
php database/migrate.php

# Seed with environment variables or CLI arguments
SEED_ADMIN_EMAIL=admin@yourdomain.com \
SEED_ADMIN_PASSWORD='Your-Secure-Password-Here!' \
php database/seed.php
```

---

## 4. Seed data

The seeder (`backend/database/seed.php`) populates:

### 4.1 Permissions (80+ entries)

All permissions from `PermissionCatalog` are inserted with `ON DUPLICATE KEY UPDATE` semantics, making the seeder idempotent.

### 4.2 Roles (10 predefined)

| Role | Description |
| --- | --- |
| Super Administrator | Unrestricted access; manages system configuration and recovery |
| Company Owner | Read-only visibility into company health and reports |
| Regional Manager | Manages operations for an assigned geographical region |
| Finance Department | Manages billing and financial operations |
| Sales Team | Manages leads and new customer acquisition |
| Customer Support | Supports customers and manages support cases |
| Installation Team / Field Technician | Manages assigned installation and repair work |
| Network Engineer | Manages network infrastructure and provisioning |
| Inventory Manager | Manages physical inventory and purchasing |
| Customer | Self-service portal access to own account, billing, payments, and support |

### 4.3 Role-permission assignments

Each role is pre-assigned the appropriate permissions from the catalog. The Super Administrator role receives the wildcard `*` permission.

### 4.4 Initial administrator

An administrator user is created **only** when both `SEED_ADMIN_EMAIL` and `SEED_ADMIN_PASSWORD` are provided:

- Email must be valid (`filter_var` checked)
- Password must be at least 8 characters
- Password is hashed with Argon2ID
- User is assigned the "Super Administrator" role

**The seeder is idempotent** — running it multiple times will update descriptions and passwords but will not duplicate data.

---

## 5. Post-migration validation

After running migrations and seed, verify the installation:

```bash
# Verify tables exist (should show 165+ application tables)
docker compose -f docker-compose.prod.yml exec mariadb \
  mariadb -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE" \
  -e "SHOW TABLES;"

# Verify RBAC data
docker compose -f docker-compose.prod.yml exec mariadb \
  mariadb -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE" \
  -e "SELECT COUNT(*) AS permission_count FROM permissions; SELECT COUNT(*) AS role_count FROM roles;"

# Verify admin user
docker compose -f docker-compose.prod.yml exec mariadb \
  mariadb -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE" \
  -e "SELECT id, name, email FROM users WHERE email='admin@yourdomain.com';"

# Verify health
curl -fsS http://localhost/healthz
curl -fsS http://localhost/readyz
```

---

## 6. Rollback

There is no automated rollback for greenfield installations. If a migration fails:

1. Check the `migrations` table for the last successfully applied migration.
2. Drop the database and re-create it:
   ```sql
   DROP DATABASE skyfi;
   CREATE DATABASE skyfi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Re-run the migration from scratch.

For future releases, individual migration rollbacks will be documented in the release-specific migration notes.

---

## 7. Migration to future versions

When upgrading from v1.0.0 to a future release:

1. Always run `--pretend` first to review pending migrations.
2. Back up the database before applying new migrations.
3. New migrations will be added with later timestamps and applied incrementally.
4. The `migrations` table tracks which files have been applied.
5. Never modify an existing migration file — only add new ones.

---

## 8. Data integrity notes

- **Foreign keys:** All cross-table references use InnoDB foreign key constraints with appropriate `ON DELETE` behavior.
- **Soft deletes:** Critical entities (customers, invoices, payments, users) use `deleted_at` columns rather than physical deletion.
- **Money columns:** All currency values are stored as `DECIMAL(12,2)` — never floating point.
- **Timestamps:** All mutable tables include `created_at` and `updated_at` columns.
- **Character set:** All tables use `utf8mb4` / `utf8mb4_unicode_ci` for full Unicode support.
