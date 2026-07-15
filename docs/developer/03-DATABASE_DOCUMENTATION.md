# Database Documentation

**Engine:** MariaDB 11.x (MySQL 8.x compatible)  
**Charset:** `utf8mb4` / `utf8mb4_unicode_ci`  
**Migrations:** 33 SQL files under `backend/database/migrations/`  
**Tables:** 165 unique application tables (plus migrator bookkeeping)

## 1. Design principles (as implemented)

1. **InnoDB only** — transactional integrity and foreign keys.
2. **3NF baseline** — denormalization only with clear operational reasons.
3. **Soft deletes** — `deleted_at` on customers, invoices, payments, users-linked critical rows where required.
4. **Audit timestamps** — `created_at` / `updated_at` on mutable tables.
5. **Money as DECIMAL** — never floating point for currency.
6. **Migrations as contract** — no manual production `ALTER TABLE`.
7. **Module-scoped SQL** — tables introduced with the owning feature migration.

## 2. Naming conventions

| Element | Convention | Example |
| --- | --- | --- |
| Tables | plural `snake_case` | `invoice_items` |
| Columns | singular `snake_case` | `billing_period_start` |
| Primary key | `id` | `BIGINT UNSIGNED AUTO_INCREMENT` |
| Foreign keys | `{entity}_id` | `customer_id` |
| Unique keys | `uk_{table}_{cols}` | `uk_customers_customer_code` |
| Indexes | `idx_{table}_{cols}` | `idx_invoices_status_due_date` |
| Foreign key constraints | `fk_{table}_{ref}` | `fk_invoices_customer` |
| Booleans / flags | `is_*` / status enums | `is_active`, `status` |

## 3. Migration runner

```bash
# List pending only
php database/migrate.php --pretend

# Apply all pending SQL files in filename order
php database/migrate.php

# Seed RBAC + admin (env-driven credentials)
php database/seed.php
```

Docker:

```bash
docker compose exec backend php database/migrate.php
docker compose exec backend php database/seed.php
```

Implementation: `backend/database/Migrator.php`, entrypoint `backend/database/migrate.php`.

**Rules**

- One logical change set per timestamped file.
- Migrations must be additive and reverse-safe when possible; destructive changes need explicit ops review.
- Seeders never commit real production passwords; use `SEED_ADMIN_EMAIL` / `SEED_ADMIN_PASSWORD`.

## 4. Tables by module

### 4.1 Authentication & RBAC

| Table | Purpose |
| --- | --- |
| `users` | Staff and portal users |
| `roles` | Named roles |
| `permissions` | Permission catalog |
| `role_user` | User ↔ role |
| `permission_role` | Role ↔ permission |
| `refresh_tokens` | Hashed refresh tokens |
| `password_resets` | Password reset tokens |
| `api_rate_limits` | Auth/API rate limit counters |

### 4.2 Customers & packages & connections

| Table | Purpose |
| --- | --- |
| `customers` | CRM customer master |
| `package_categories`, `packages` | Service catalog |
| `package_prices`, `package_pricing_settings` | Pricing |
| `package_bandwidth_profiles`, `package_network_profiles` | Network profiles |
| `package_customer_rules`, `package_billing_settings`, `package_technical_profiles` | Package policy |
| `connections` | Active/pending customer services |

### 4.3 Billing & payments

| Table | Purpose |
| --- | --- |
| `invoices`, `invoice_items` | Invoice header/lines |
| `billing_schedules`, `late_fee_rules` | Recurring billing rules |
| `invoice_activities` | Invoice audit trail |
| `payment_methods`, `payment_accounts` | Cash/bank channels |
| `payments`, `payment_allocations` | Receipts and invoice allocation |
| `receipts`, `payment_refunds`, `payment_activities` | Documents and history |
| `customer_credit_ledger` | Customer credit balance events |
| `payment_attachments`, `payment_financial_events` | Attachments and finance hooks |

### 4.4 Finance

| Table | Purpose |
| --- | --- |
| `chart_of_accounts` | Account hierarchy |
| `financial_accounts` | Operating accounts |
| `general_ledger` | Posted ledger rows |
| `journal_entries`, `journal_entry_lines` | Double-entry journals |
| `expenses`, `revenue` | Operating expense/revenue records |

### 4.5 Network (MikroTik, PPPoE, Hotspot, Infrastructure, Monitoring)

| Table group | Tables |
| --- | --- |
| MikroTik | `mikrotik_router_groups`, `mikrotik_router_tags`, `mikrotik_routers`, `mikrotik_router_tag_assignments`, `mikrotik_router_health_snapshots` |
| PPPoE | `pppoe_accounts`, `pppoe_session_history`, `pppoe_sync_logs`, `pppoe_auth_logs` |
| Hotspot | `hotspot_profiles`, `hotspot_users`, `hotspot_voucher_batches`, `hotspot_vouchers`, `hotspot_session_history`, `hotspot_sync_logs`, `hotspot_login_history` |
| Infrastructure | `pop_sites`, `towers`, `sectors`, `network_devices` |
| Monitoring | `monitoring_events`, `monitoring_device_status_history`, `monitoring_interface_snapshots`, `monitoring_alerts`, `monitoring_alert_history`, `monitoring_sync_events` |

### 4.6 Support

`support_teams`, `support_team_members`, `ticket_categories`, `sla_policies`, `support_tickets`, `ticket_comments`, `ticket_assignments`, `ticket_history`

### 4.7 Inventory, purchasing, vendors

| Area | Tables |
| --- | --- |
| Inventory catalog | `inventory_categories`, `inventory_brands`, `inventory_product_models`, `inventory_units`, `inventory_products`, `inventory_product_vendors` |
| Warehousing | `warehouses`, `warehouse_locations`, `inventory_stock_balances`, `inventory_stock_movements`, `inventory_stock_movement_lines` |
| Assets | `inventory_assets`, `inventory_asset_assignments`, `inventory_asset_events` |
| Transfers | `inventory_warehouse_transfers`, lines, assets |
| Finance bridge | `inventory_accounting_settings`, `inventory_finance_postings` |
| Base vendors | `vendors` (inventory-linked supplier master) |
| Purchasing | `purchase_requests` (+ items, approvals), `purchase_orders` (+ items, approvals), `goods_receipts` (+ items), `supplier_invoices`, `purchasing_finance_postings` |
| Vendor CRM | `supplier_categories`, assignments, `supplier_contacts`, `supplier_contracts`, `supplier_quotations` (+ items), `supplier_ratings` |

### 4.8 Field service

`field_teams`, `technicians`, `field_team_members`, `technician_skills`, `technician_service_areas`, `technician_availability`, `installation_requests`, `work_orders`, `field_visits`, `work_logs`, `work_order_materials`, `work_order_history`

### 4.9 Reports, system, notifications

| Area | Tables |
| --- | --- |
| Reports | `report_templates`, `saved_reports`, `scheduled_reports`, `report_export_history` |
| System | `companies`, `branches`, `departments`, `system_settings`, `branding_settings`, `localization_settings`, `notification_preferences` |
| Notifications | `notification_events`, `notification_templates`, `notifications`, `user_notification_preferences`, `notification_deliveries` |

### 4.10 Audit, backup, integration, workflow

| Area | Tables |
| --- | --- |
| Audit | `audit_logs`, `activity_events`, `compliance_policies`, `retention_policies`, `audit_exports` |
| Backup | `backup_storage_providers`, `backup_schedules`, `backup_jobs`, `backup_files`, `restore_history`, `verification_history`, `dr_plans` |
| Integration | `client_applications`, `api_keys`, `event_registry`, `webhooks`, `webhook_deliveries`, `connector_configurations`, `api_request_logs` |
| Workflow | `workflows`, `workflow_versions`, `workflow_triggers`, `workflow_conditions`, `workflow_actions`, `workflow_executions` |

## 5. Core column examples

### `customers` (excerpt)

- Identity: `customer_code` (unique), `full_name`, `cnic` (unique nullable), `phone`
- Location: `address`, `city`, `area`
- Lifecycle: `status` enum `lead|prospect|active|suspended|disconnected|archived`
- Soft delete: `deleted_at`
- Audit: `created_by`, `updated_by`, timestamps

### `invoices` (excerpt)

- Keys: `invoice_number` (unique), `customer_id`, `connection_id`, `package_id`
- Status: `draft|pending|issued|partially_paid|paid|overdue|cancelled|void`
- Money: `subtotal`, `tax_amount`, `discount_amount`, `late_fee_amount`, `previous_balance`, `total_amount`, `balance_due` as `DECIMAL(12,2)`
- Period: `billing_period_start/end`, `issue_date`, `due_date`
- Currency default: `PKR`

### `connections` (excerpt)

- Type: `pppoe|hotspot|static_ip`
- Status: `pending|scheduled|installing|active|suspended|disconnected|cancelled|archived`
- Network fields: PPPoE credentials (encrypted password), static IP, MAC, VLAN, radius/queue names
- Infrastructure linkage fields and installation metadata

## 6. Indexing strategy

Implemented patterns:

- Primary keys on all tables
- Foreign key indexes for join/filter performance
- Composite indexes for common list filters (e.g. `idx_invoices_customer_id_status`, `idx_invoices_status_due_date`)
- Soft-delete indexes (`deleted_at`) on large soft-deleted tables
- Dedicated performance migration: `2026_08_11_000001_add_performance_indexes.sql`

When adding queries:

1. Prefer existing indexes.
2. Use `EXPLAIN` on new list/filter queries.
3. Add indexes in a new migration—not ad hoc in production.

## 7. Integrity and cascading

Typical FK behaviors in this codebase:

- `ON DELETE RESTRICT` for financial/customer ownership edges (prevent accidental purge)
- `ON DELETE SET NULL` for optional audit actors (`updated_by`)
- `ON UPDATE CASCADE` widely used for surrogate key stability

Do not introduce `ON DELETE CASCADE` on financial history without an explicit product decision.

## 8. Environments

| Environment | Database | Notes |
| --- | --- | --- |
| Local Compose | `skyfi` on service `mariadb` | Credentials from `.env` |
| PHPUnit | `skyfi_test` (see `phpunit.xml`) | Isolated test DB when integration tests run |
| Production | Host/managed MariaDB | Backups via ops runbooks; volume `mariadb-data` |

## 9. Backup notes for developers

Logical dump example (dev/prod Compose):

```bash
docker compose -f docker-compose.prod.yml exec mariadb \
  mariadb-dump -uroot -p"$MARIADB_ROOT_PASSWORD" \
  --single-transaction --routines --triggers skyfi \
  > backups/mariadb/skyfi-$(date +%Y%m%d%H%M%S).sql
```

Application-level backup jobs also exist in the Backup module; see ops docs (Phase 5) and `docs/deployment/DEPLOYMENT_GUIDE.md`.

## 10. Related documents

- [ER Diagrams](./04-ER_DIAGRAMS.md)
- Phase 1 database review: `docs/production-readiness/DATABASE_REVIEW.md`
- Original design draft: `docs/Document 10 Database Architecture.md`
