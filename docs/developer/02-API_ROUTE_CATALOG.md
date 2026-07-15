# API Route Catalog (Generated)

This catalog lists every unique HTTP route registered via `$router->add(...)` in the backend as of the Phase 4 documentation pass.

- **Source:** `backend/routes/*.php` and `backend/src/*/Routes/*.php`
- **Base path:** `/api/v1` (except operational health endpoints if registered separately)
- **Regeneration:** re-scan route registrations when adding endpoints and update this file in the same PR

Total unique API routes: 537

### audit

| Method | Path |
| --- | --- |
| `GET` | `/api/v1/audit/activity` |
| `GET` | `/api/v1/audit/dashboard` |
| `GET` | `/api/v1/audit/exports` |
| `GET` | `/api/v1/audit/exports/{id}/download` |
| `GET` | `/api/v1/audit/filter-options` |
| `GET` | `/api/v1/audit/logs` |
| `GET` | `/api/v1/audit/logs/{id}` |
| `GET` | `/api/v1/audit/resource-history` |
| `GET` | `/api/v1/audit/users/{id}/activity` |
| `POST` | `/api/v1/audit/export` |

### auth

| Method | Path |
| --- | --- |
| `POST` | `/api/v1/auth/change-password` |
| `POST` | `/api/v1/auth/forgot-password` |
| `POST` | `/api/v1/auth/login` |
| `POST` | `/api/v1/auth/logout` |
| `POST` | `/api/v1/auth/refresh` |
| `POST` | `/api/v1/auth/reset-password` |

### backup

| Method | Path |
| --- | --- |
| `DELETE` | `/api/v1/backup/schedules/{id}` |
| `GET` | `/api/v1/backup/dr-plans` |
| `GET` | `/api/v1/backup/dr-plans/{id}` |
| `GET` | `/api/v1/backup/files` |
| `GET` | `/api/v1/backup/files/{id}/verification-history` |
| `GET` | `/api/v1/backup/jobs` |
| `GET` | `/api/v1/backup/restore/history` |
| `GET` | `/api/v1/backup/schedules` |
| `GET` | `/api/v1/backup/statistics` |
| `GET` | `/api/v1/backup/storage-providers` |
| `POST` | `/api/v1/backup/files/{id}/verify` |
| `POST` | `/api/v1/backup/jobs/manual` |
| `POST` | `/api/v1/backup/restore/execute` |
| `POST` | `/api/v1/backup/schedules` |
| `POST` | `/api/v1/backup/storage-providers` |
| `PUT` | `/api/v1/backup/dr-plans/{id}` |
| `PUT` | `/api/v1/backup/schedules/{id}` |
| `PUT` | `/api/v1/backup/storage-providers/{id}` |

### compliance

| Method | Path |
| --- | --- |
| `DELETE` | `/api/v1/compliance/policies/{id}` |
| `DELETE` | `/api/v1/compliance/retention/{id}` |
| `GET` | `/api/v1/compliance/policies` |
| `GET` | `/api/v1/compliance/policies/{id}` |
| `GET` | `/api/v1/compliance/retention` |
| `GET` | `/api/v1/compliance/retention/{id}` |
| `POST` | `/api/v1/compliance/policies` |
| `POST` | `/api/v1/compliance/retention` |
| `PUT` | `/api/v1/compliance/policies/{id}` |
| `PUT` | `/api/v1/compliance/retention/{id}` |

### connections

| Method | Path |
| --- | --- |
| `DELETE` | `/api/v1/connections/{id}` |
| `GET` | `/api/v1/connections` |
| `GET` | `/api/v1/connections/{id}` |
| `PATCH` | `/api/v1/connections/{id}/activate` |
| `PATCH` | `/api/v1/connections/{id}/disconnect` |
| `PATCH` | `/api/v1/connections/{id}/suspend` |
| `PATCH` | `/api/v1/connections/{id}/transfer` |
| `POST` | `/api/v1/connections` |
| `PUT` | `/api/v1/connections/{id}` |

### customers

| Method | Path |
| --- | --- |
| `DELETE` | `/api/v1/customers/{id}` |
| `GET` | `/api/v1/customers` |
| `GET` | `/api/v1/customers/{id}` |
| `PATCH` | `/api/v1/customers/{id}/status` |
| `POST` | `/api/v1/customers` |
| `PUT` | `/api/v1/customers/{id}` |

### dashboard

| Method | Path |
| --- | --- |
| `GET` | `/api/v1/dashboard` |

### field-service

| Method | Path |
| --- | --- |
| `DELETE` | `/api/v1/field-service/installation-requests/{id}` |
| `DELETE` | `/api/v1/field-service/technicians/{id}` |
| `DELETE` | `/api/v1/field-service/work-orders/{id}` |
| `GET` | `/api/v1/field-service/dashboard` |
| `GET` | `/api/v1/field-service/installation-requests` |
| `GET` | `/api/v1/field-service/installation-requests/{id}` |
| `GET` | `/api/v1/field-service/lookups/{resource}` |
| `GET` | `/api/v1/field-service/schedule` |
| `GET` | `/api/v1/field-service/schedule/unscheduled` |
| `GET` | `/api/v1/field-service/technicians` |
| `GET` | `/api/v1/field-service/technicians/{id}` |
| `GET` | `/api/v1/field-service/technicians/{id}/schedule` |
| `GET` | `/api/v1/field-service/work-orders` |
| `GET` | `/api/v1/field-service/work-orders/{id}` |
| `GET` | `/api/v1/field-service/work-orders/{id}/timeline` |
| `POST` | `/api/v1/field-service/installation-requests` |
| `POST` | `/api/v1/field-service/technicians` |
| `POST` | `/api/v1/field-service/work-orders` |
| `PUT` | `/api/v1/field-service/installation-requests/{id}` |
| `PUT` | `/api/v1/field-service/technicians/{id}` |
| `PUT` | `/api/v1/field-service/work-orders/{id}` |

### finance

| Method | Path |
| --- | --- |
| `GET` | `/api/v1/finance/accounts` |
| `GET` | `/api/v1/finance/chart-of-accounts` |
| `GET` | `/api/v1/finance/dashboard` |
| `GET` | `/api/v1/finance/expenses` |
| `GET` | `/api/v1/finance/journal-entries` |
| `GET` | `/api/v1/finance/ledger` |
| `GET` | `/api/v1/finance/revenue` |
| `POST` | `/api/v1/finance/accounts` |
| `POST` | `/api/v1/finance/chart-of-accounts` |
| `POST` | `/api/v1/finance/expenses` |
| `POST` | `/api/v1/finance/journal-entries` |
| `POST` | `/api/v1/finance/revenue` |

### hotspot

| Method | Path |
| --- | --- |
| `DELETE` | `/api/v1/hotspot/profiles/{id}` |
| `DELETE` | `/api/v1/hotspot/users/{id}` |
| `GET` | `/api/v1/hotspot/profiles` |
| `GET` | `/api/v1/hotspot/profiles/{id}` |
| `GET` | `/api/v1/hotspot/routers/{routerId}/profiles` |
| `GET` | `/api/v1/hotspot/sessions/active` |
| `GET` | `/api/v1/hotspot/sessions/history` |
| `GET` | `/api/v1/hotspot/sessions/login-history` |
| `GET` | `/api/v1/hotspot/sync/logs` |
| `GET` | `/api/v1/hotspot/users` |
| `GET` | `/api/v1/hotspot/users/{id}` |
| `GET` | `/api/v1/hotspot/users/{id}/sessions/history` |
| `GET` | `/api/v1/hotspot/users/{id}/statistics` |
| `GET` | `/api/v1/hotspot/vouchers` |
| `GET` | `/api/v1/hotspot/vouchers/batch/{batchId}/print` |
| `GET` | `/api/v1/hotspot/vouchers/batches` |
| `GET` | `/api/v1/hotspot/vouchers/stats` |
| `GET` | `/api/v1/hotspot/vouchers/{id}` |
| `PATCH` | `/api/v1/hotspot/users/{id}/disable` |
| `PATCH` | `/api/v1/hotspot/users/{id}/enable` |
| `POST` | `/api/v1/hotspot/profiles` |
| `POST` | `/api/v1/hotspot/sessions/active/disconnect` |
| `POST` | `/api/v1/hotspot/sessions/force-logout` |
| `POST` | `/api/v1/hotspot/sync/detect-missing` |
| `POST` | `/api/v1/hotspot/sync/import` |
| `POST` | `/api/v1/hotspot/sync/import-profiles` |
| `POST` | `/api/v1/hotspot/sync/repair` |
| `POST` | `/api/v1/hotspot/sync/router/{routerId}` |
| `POST` | `/api/v1/hotspot/sync/user/{id}` |
| `POST` | `/api/v1/hotspot/users` |
| `POST` | `/api/v1/hotspot/users/bulk-import` |
| `POST` | `/api/v1/hotspot/users/{id}/reset-password` |
| `POST` | `/api/v1/hotspot/users/{id}/resume` |
| `POST` | `/api/v1/hotspot/users/{id}/suspend` |
| `POST` | `/api/v1/hotspot/vouchers/generate` |
| `POST` | `/api/v1/hotspot/vouchers/{id}/revoke` |
| `PUT` | `/api/v1/hotspot/profiles/{id}` |
| `PUT` | `/api/v1/hotspot/users/{id}` |
| `PUT` | `/api/v1/hotspot/users/{id}/profile` |
| `PUT` | `/api/v1/hotspot/users/{id}/router` |

### infrastructure

| Method | Path |
| --- | --- |
| `DELETE` | `/api/v1/infrastructure/devices/{id}` |
| `DELETE` | `/api/v1/infrastructure/pop-sites/{id}` |
| `DELETE` | `/api/v1/infrastructure/sectors/{id}` |
| `DELETE` | `/api/v1/infrastructure/towers/{id}` |
| `GET` | `/api/v1/infrastructure/dashboard` |
| `GET` | `/api/v1/infrastructure/devices` |
| `GET` | `/api/v1/infrastructure/devices/by-type/{type}` |
| `GET` | `/api/v1/infrastructure/devices/{id}` |
| `GET` | `/api/v1/infrastructure/pop-sites` |
| `GET` | `/api/v1/infrastructure/pop-sites/map-points` |
| `GET` | `/api/v1/infrastructure/pop-sites/{id}` |
| `GET` | `/api/v1/infrastructure/pop-sites/{id}/devices` |
| `GET` | `/api/v1/infrastructure/pop-sites/{id}/towers` |
| `GET` | `/api/v1/infrastructure/sectors` |
| `GET` | `/api/v1/infrastructure/sectors/coverage` |
| `GET` | `/api/v1/infrastructure/sectors/{id}` |
| `GET` | `/api/v1/infrastructure/sectors/{id}/connections` |
| `GET` | `/api/v1/infrastructure/towers` |
| `GET` | `/api/v1/infrastructure/towers/map-points` |
| `GET` | `/api/v1/infrastructure/towers/{id}` |
| `GET` | `/api/v1/infrastructure/towers/{id}/devices` |
| `GET` | `/api/v1/infrastructure/towers/{id}/sectors` |
| `PATCH` | `/api/v1/infrastructure/devices/{id}/status` |
| `PATCH` | `/api/v1/infrastructure/pop-sites/{id}/status` |
| `PATCH` | `/api/v1/infrastructure/sectors/{id}/status` |
| `PATCH` | `/api/v1/infrastructure/towers/{id}/status` |
| `POST` | `/api/v1/infrastructure/devices` |
| `POST` | `/api/v1/infrastructure/pop-sites` |
| `POST` | `/api/v1/infrastructure/sectors` |
| `POST` | `/api/v1/infrastructure/towers` |
| `PUT` | `/api/v1/infrastructure/devices/{id}` |
| `PUT` | `/api/v1/infrastructure/pop-sites/{id}` |
| `PUT` | `/api/v1/infrastructure/sectors/{id}` |
| `PUT` | `/api/v1/infrastructure/towers/{id}` |

### integration

| Method | Path |
| --- | --- |
| `DELETE` | `/api/v1/integration/api-keys/{id}` |
| `DELETE` | `/api/v1/integration/applications/{id}` |
| `DELETE` | `/api/v1/integration/webhooks/{id}` |
| `GET` | `/api/v1/integration/api-keys` |
| `GET` | `/api/v1/integration/api-keys/{id}` |
| `GET` | `/api/v1/integration/applications` |
| `GET` | `/api/v1/integration/applications/{id}` |
| `GET` | `/api/v1/integration/connectors` |
| `GET` | `/api/v1/integration/connectors/{type}` |
| `GET` | `/api/v1/integration/dashboard` |
| `GET` | `/api/v1/integration/deliveries` |
| `GET` | `/api/v1/integration/deliveries/{id}` |
| `GET` | `/api/v1/integration/events` |
| `GET` | `/api/v1/integration/events/{id}` |
| `GET` | `/api/v1/integration/request-logs` |
| `GET` | `/api/v1/integration/webhooks` |
| `GET` | `/api/v1/integration/webhooks/{id}` |
| `GET` | `/api/v1/integration/webhooks/{webhookId}/deliveries` |
| `POST` | `/api/v1/integration/api-keys` |
| `POST` | `/api/v1/integration/api-keys/{id}/regenerate` |
| `POST` | `/api/v1/integration/applications` |
| `POST` | `/api/v1/integration/connectors/{type}/test` |
| `POST` | `/api/v1/integration/deliveries/{id}/retry` |
| `POST` | `/api/v1/integration/webhooks` |
| `POST` | `/api/v1/integration/webhooks/inbound` |
| `POST` | `/api/v1/integration/webhooks/{id}/rotate-secret` |
| `POST` | `/api/v1/integration/webhooks/{id}/test` |
| `PUT` | `/api/v1/integration/api-keys/{id}` |
| `PUT` | `/api/v1/integration/applications/{id}` |
| `PUT` | `/api/v1/integration/connectors/{type}` |
| `PUT` | `/api/v1/integration/webhooks/{id}` |

### inventory

| Method | Path |
| --- | --- |
| `DELETE` | `/api/v1/inventory/` |
| `DELETE` | `/api/v1/inventory/assets/{id}` |
| `DELETE` | `/api/v1/inventory/products/{id}` |
| `DELETE` | `/api/v1/inventory/transfers/{id}` |
| `DELETE` | `/api/v1/inventory/warehouses/{id}` |
| `DELETE` | `/api/v1/inventory/warehouses/{id}/locations/{locationId}` |
| `GET` | `/api/v1/inventory/` |
| `GET` | `/api/v1/inventory/assets` |
| `GET` | `/api/v1/inventory/assets/{id}` |
| `GET` | `/api/v1/inventory/assets/{id}/timeline` |
| `GET` | `/api/v1/inventory/dashboard` |
| `GET` | `/api/v1/inventory/finance-postings` |
| `GET` | `/api/v1/inventory/lookups/{resource}` |
| `GET` | `/api/v1/inventory/products` |
| `GET` | `/api/v1/inventory/products/stock` |
| `GET` | `/api/v1/inventory/products/{id}` |
| `GET` | `/api/v1/inventory/search` |
| `GET` | `/api/v1/inventory/settings/accounting` |
| `GET` | `/api/v1/inventory/stock` |
| `GET` | `/api/v1/inventory/stock-movements` |
| `GET` | `/api/v1/inventory/stock-movements/{id}` |
| `GET` | `/api/v1/inventory/transfers` |
| `GET` | `/api/v1/inventory/transfers/{id}` |
| `GET` | `/api/v1/inventory/warehouses` |
| `GET` | `/api/v1/inventory/warehouses/{id}` |
| `GET` | `/api/v1/inventory/warehouses/{id}/locations` |
| `PATCH` | `/api/v1/inventory/assets/{id}/status` |
| `PATCH` | `/api/v1/inventory/warehouses/{id}/status` |
| `POST` | `/api/v1/inventory/` |
| `POST` | `/api/v1/inventory/assets` |
| `POST` | `/api/v1/inventory/assets/{id}/assign` |
| `POST` | `/api/v1/inventory/assets/{id}/return` |
| `POST` | `/api/v1/inventory/finance-postings/{id}/retry` |
| `POST` | `/api/v1/inventory/products` |
| `POST` | `/api/v1/inventory/stock-movements/` |
| `POST` | `/api/v1/inventory/stock-movements/{id}/reverse` |
| `POST` | `/api/v1/inventory/transfers` |
| `POST` | `/api/v1/inventory/transfers/{id}/` |
| `POST` | `/api/v1/inventory/warehouses` |
| `POST` | `/api/v1/inventory/warehouses/{id}/locations` |
| `PUT` | `/api/v1/inventory/` |
| `PUT` | `/api/v1/inventory/assets/{id}` |
| `PUT` | `/api/v1/inventory/products/{id}` |
| `PUT` | `/api/v1/inventory/settings/accounting` |
| `PUT` | `/api/v1/inventory/transfers/{id}` |
| `PUT` | `/api/v1/inventory/warehouses/{id}` |
| `PUT` | `/api/v1/inventory/warehouses/{id}/locations/{locationId}` |

### invoices

| Method | Path |
| --- | --- |
| `DELETE` | `/api/v1/invoices/{id}` |
| `GET` | `/api/v1/invoices` |
| `GET` | `/api/v1/invoices/statistics` |
| `GET` | `/api/v1/invoices/{id}` |
| `GET` | `/api/v1/invoices/{id}/activity` |
| `PATCH` | `/api/v1/invoices/{id}/status` |
| `POST` | `/api/v1/invoices` |
| `POST` | `/api/v1/invoices/bulk-generate` |
| `POST` | `/api/v1/invoices/generate` |
| `PUT` | `/api/v1/invoices/{id}` |

### me

| Method | Path |
| --- | --- |
| `GET` | `/api/v1/me/permissions` |

### mikrotik

| Method | Path |
| --- | --- |
| `DELETE` | `/api/v1/mikrotik/router-groups/{id}` |
| `DELETE` | `/api/v1/mikrotik/router-tags/{id}` |
| `DELETE` | `/api/v1/mikrotik/routers/{id}` |
| `GET` | `/api/v1/mikrotik/router-groups` |
| `GET` | `/api/v1/mikrotik/router-tags` |
| `GET` | `/api/v1/mikrotik/routers` |
| `GET` | `/api/v1/mikrotik/routers/{id}` |
| `GET` | `/api/v1/mikrotik/routers/{id}/health` |
| `GET` | `/api/v1/mikrotik/routers/{id}/statistics` |
| `PATCH` | `/api/v1/mikrotik/routers/{id}/disable` |
| `PATCH` | `/api/v1/mikrotik/routers/{id}/enable` |
| `POST` | `/api/v1/mikrotik/router-groups` |
| `POST` | `/api/v1/mikrotik/router-tags` |
| `POST` | `/api/v1/mikrotik/routers` |
| `POST` | `/api/v1/mikrotik/routers/{id}/discover` |
| `POST` | `/api/v1/mikrotik/routers/{id}/health/check` |
| `POST` | `/api/v1/mikrotik/routers/{id}/test-connection` |
| `POST` | `/api/v1/mikrotik/test-connection` |
| `PUT` | `/api/v1/mikrotik/router-groups/{id}` |
| `PUT` | `/api/v1/mikrotik/router-tags/{id}` |
| `PUT` | `/api/v1/mikrotik/routers/{id}` |
| `PUT` | `/api/v1/mikrotik/routers/{id}/tags` |

### monitoring

| Method | Path |
| --- | --- |
| `GET` | `/api/v1/monitoring/alerts` |
| `GET` | `/api/v1/monitoring/alerts/{id}` |
| `GET` | `/api/v1/monitoring/dashboard` |
| `GET` | `/api/v1/monitoring/devices/health` |
| `GET` | `/api/v1/monitoring/events` |
| `GET` | `/api/v1/monitoring/interfaces` |
| `GET` | `/api/v1/monitoring/routers/{id}/metrics` |
| `GET` | `/api/v1/monitoring/sync-history` |
| `POST` | `/api/v1/monitoring/alerts` |
| `POST` | `/api/v1/monitoring/alerts/{id}/acknowledge` |
| `POST` | `/api/v1/monitoring/alerts/{id}/dismiss` |
| `POST` | `/api/v1/monitoring/alerts/{id}/resolve` |
| `POST` | `/api/v1/monitoring/poll-all` |
| `POST` | `/api/v1/monitoring/routers/{id}/poll` |

### notifications

| Method | Path |
| --- | --- |
| `DELETE` | `/api/v1/notifications/templates/{id}` |
| `DELETE` | `/api/v1/notifications/{id}` |
| `GET` | `/api/v1/notifications` |
| `GET` | `/api/v1/notifications/catalog` |
| `GET` | `/api/v1/notifications/deliveries` |
| `GET` | `/api/v1/notifications/deliveries/{id}` |
| `GET` | `/api/v1/notifications/events` |
| `GET` | `/api/v1/notifications/events/{id}` |
| `GET` | `/api/v1/notifications/preferences` |
| `GET` | `/api/v1/notifications/templates` |
| `GET` | `/api/v1/notifications/templates/{id}` |
| `GET` | `/api/v1/notifications/unread-count` |
| `GET` | `/api/v1/notifications/{id}` |
| `PATCH` | `/api/v1/notifications/read-all` |
| `PATCH` | `/api/v1/notifications/{id}/archive` |
| `PATCH` | `/api/v1/notifications/{id}/read` |
| `POST` | `/api/v1/notifications/dispatch` |
| `POST` | `/api/v1/notifications/templates` |
| `POST` | `/api/v1/notifications/templates/{id}/preview` |
| `PUT` | `/api/v1/notifications/preferences` |
| `PUT` | `/api/v1/notifications/templates/{id}` |

### payments

| Method | Path |
| --- | --- |
| `DELETE` | `/api/v1/payments/{id}` |
| `GET` | `/api/v1/payments` |
| `GET` | `/api/v1/payments/accounts` |
| `GET` | `/api/v1/payments/export` |
| `GET` | `/api/v1/payments/lookups` |
| `GET` | `/api/v1/payments/methods` |
| `GET` | `/api/v1/payments/statistics` |
| `GET` | `/api/v1/payments/{id}` |
| `GET` | `/api/v1/payments/{id}/activity` |
| `GET` | `/api/v1/payments/{id}/allocations` |
| `GET` | `/api/v1/payments/{id}/receipt` |
| `GET` | `/api/v1/payments/{id}/receipt/pdf` |
| `POST` | `/api/v1/payments` |
| `POST` | `/api/v1/payments/bulk` |
| `POST` | `/api/v1/payments/receive` |
| `POST` | `/api/v1/payments/{id}/allocate` |
| `POST` | `/api/v1/payments/{id}/refund` |
| `POST` | `/api/v1/payments/{id}/reverse` |
| `PUT` | `/api/v1/payments/{id}` |

### permissions

| Method | Path |
| --- | --- |
| `GET` | `/api/v1/permissions` |

### portal

| Method | Path |
| --- | --- |
| `GET` | `/api/v1/portal/balance` |
| `GET` | `/api/v1/portal/connection` |
| `GET` | `/api/v1/portal/dashboard` |
| `GET` | `/api/v1/portal/invoices` |
| `GET` | `/api/v1/portal/invoices/{id}` |
| `GET` | `/api/v1/portal/invoices/{id}/pdf` |
| `GET` | `/api/v1/portal/notifications` |
| `GET` | `/api/v1/portal/payments` |
| `GET` | `/api/v1/portal/payments/{id}` |
| `GET` | `/api/v1/portal/payments/{id}/receipt` |
| `GET` | `/api/v1/portal/preferences` |
| `GET` | `/api/v1/portal/profile` |
| `GET` | `/api/v1/portal/tickets` |
| `GET` | `/api/v1/portal/tickets/{id}` |
| `PATCH` | `/api/v1/portal/notifications/read-all` |
| `PATCH` | `/api/v1/portal/notifications/{id}/archive` |
| `PATCH` | `/api/v1/portal/notifications/{id}/read` |
| `POST` | `/api/v1/portal/tickets` |
| `POST` | `/api/v1/portal/tickets/{id}/close-request` |
| `POST` | `/api/v1/portal/tickets/{id}/reply` |
| `PUT` | `/api/v1/portal/preferences` |
| `PUT` | `/api/v1/portal/profile` |

### pppoe

| Method | Path |
| --- | --- |
| `DELETE` | `/api/v1/pppoe/accounts/{id}` |
| `GET` | `/api/v1/pppoe/accounts` |
| `GET` | `/api/v1/pppoe/accounts/{id}` |
| `GET` | `/api/v1/pppoe/accounts/{id}/sessions/history` |
| `GET` | `/api/v1/pppoe/accounts/{id}/statistics` |
| `GET` | `/api/v1/pppoe/routers/{routerId}/profiles` |
| `GET` | `/api/v1/pppoe/sessions/active` |
| `GET` | `/api/v1/pppoe/sessions/history` |
| `GET` | `/api/v1/pppoe/sync/logs` |
| `PATCH` | `/api/v1/pppoe/accounts/{id}/disable` |
| `PATCH` | `/api/v1/pppoe/accounts/{id}/enable` |
| `POST` | `/api/v1/pppoe/accounts` |
| `POST` | `/api/v1/pppoe/accounts/{id}/reconnect` |
| `POST` | `/api/v1/pppoe/accounts/{id}/reset-password` |
| `POST` | `/api/v1/pppoe/accounts/{id}/resume` |
| `POST` | `/api/v1/pppoe/accounts/{id}/suspend` |
| `POST` | `/api/v1/pppoe/sessions/active/disconnect` |
| `POST` | `/api/v1/pppoe/sync/account/{id}` |
| `POST` | `/api/v1/pppoe/sync/detect-missing` |
| `POST` | `/api/v1/pppoe/sync/import` |
| `POST` | `/api/v1/pppoe/sync/repair` |
| `POST` | `/api/v1/pppoe/sync/router/{routerId}` |
| `PUT` | `/api/v1/pppoe/accounts/{id}` |
| `PUT` | `/api/v1/pppoe/accounts/{id}/package` |

### purchasing

| Method | Path |
| --- | --- |
| `GET` | `/api/v1/purchasing/dashboard` |
| `GET` | `/api/v1/purchasing/finance-postings` |
| `GET` | `/api/v1/purchasing/goods-receipts` |
| `GET` | `/api/v1/purchasing/goods-receipts/{id}` |
| `GET` | `/api/v1/purchasing/orders` |
| `GET` | `/api/v1/purchasing/orders/{id}` |
| `GET` | `/api/v1/purchasing/requests` |
| `GET` | `/api/v1/purchasing/requests/{id}` |
| `GET` | `/api/v1/purchasing/supplier-invoices` |
| `GET` | `/api/v1/purchasing/supplier-invoices/{id}` |
| `POST` | `/api/v1/purchasing/goods-receipts` |
| `POST` | `/api/v1/purchasing/goods-receipts/{id}/return` |
| `POST` | `/api/v1/purchasing/orders` |
| `POST` | `/api/v1/purchasing/orders/{id}/approve` |
| `POST` | `/api/v1/purchasing/orders/{id}/cancel` |
| `POST` | `/api/v1/purchasing/orders/{id}/close` |
| `POST` | `/api/v1/purchasing/orders/{id}/reject` |
| `POST` | `/api/v1/purchasing/orders/{id}/submit` |
| `POST` | `/api/v1/purchasing/requests` |
| `POST` | `/api/v1/purchasing/requests/{id}/approve` |
| `POST` | `/api/v1/purchasing/requests/{id}/cancel` |
| `POST` | `/api/v1/purchasing/requests/{id}/reject` |
| `POST` | `/api/v1/purchasing/requests/{id}/submit` |
| `POST` | `/api/v1/purchasing/supplier-invoices` |
| `PUT` | `/api/v1/purchasing/orders/{id}` |
| `PUT` | `/api/v1/purchasing/requests/{id}` |
| `PUT` | `/api/v1/purchasing/supplier-invoices/{id}` |

### reports

| Method | Path |
| --- | --- |
| `DELETE` | `/api/v1/reports/exports/{id}` |
| `DELETE` | `/api/v1/reports/saved/{id}` |
| `DELETE` | `/api/v1/reports/schedules/{id}` |
| `DELETE` | `/api/v1/reports/templates/{id}` |
| `GET` | `/api/v1/reports/catalog` |
| `GET` | `/api/v1/reports/dashboards/{dashboard}` |
| `GET` | `/api/v1/reports/exports` |
| `GET` | `/api/v1/reports/exports/{id}` |
| `GET` | `/api/v1/reports/exports/{id}/download` |
| `GET` | `/api/v1/reports/filters` |
| `GET` | `/api/v1/reports/saved` |
| `GET` | `/api/v1/reports/saved/{id}` |
| `GET` | `/api/v1/reports/schedules` |
| `GET` | `/api/v1/reports/schedules/{id}` |
| `GET` | `/api/v1/reports/templates` |
| `GET` | `/api/v1/reports/templates/{id}` |
| `POST` | `/api/v1/reports/exports` |
| `POST` | `/api/v1/reports/generate` |
| `POST` | `/api/v1/reports/saved` |
| `POST` | `/api/v1/reports/saved/{id}/run` |
| `POST` | `/api/v1/reports/schedules` |
| `POST` | `/api/v1/reports/templates` |
| `PUT` | `/api/v1/reports/saved/{id}` |
| `PUT` | `/api/v1/reports/schedules/{id}` |
| `PUT` | `/api/v1/reports/templates/{id}` |

### roles

| Method | Path |
| --- | --- |
| `DELETE` | `/api/v1/roles/{id}` |
| `GET` | `/api/v1/roles` |
| `GET` | `/api/v1/roles/{id}` |
| `POST` | `/api/v1/roles` |
| `PUT` | `/api/v1/roles/{id}` |

### system

| Method | Path |
| --- | --- |
| `DELETE` | `/api/v1/system/branches/{id}` |
| `DELETE` | `/api/v1/system/departments/{id}` |
| `GET` | `/api/v1/system/branches` |
| `GET` | `/api/v1/system/branches/{id}` |
| `GET` | `/api/v1/system/branding` |
| `GET` | `/api/v1/system/company` |
| `GET` | `/api/v1/system/configuration` |
| `GET` | `/api/v1/system/dashboard` |
| `GET` | `/api/v1/system/departments` |
| `GET` | `/api/v1/system/departments/{id}` |
| `GET` | `/api/v1/system/localization` |
| `GET` | `/api/v1/system/localization/options` |
| `GET` | `/api/v1/system/notifications` |
| `GET` | `/api/v1/system/settings` |
| `POST` | `/api/v1/system/branches` |
| `POST` | `/api/v1/system/branches/{id}/activate` |
| `POST` | `/api/v1/system/branches/{id}/deactivate` |
| `POST` | `/api/v1/system/branding/assets` |
| `POST` | `/api/v1/system/departments` |
| `POST` | `/api/v1/system/departments/{id}/activate` |
| `POST` | `/api/v1/system/departments/{id}/deactivate` |
| `POST` | `/api/v1/system/settings/maintenance/disable` |
| `POST` | `/api/v1/system/settings/maintenance/enable` |
| `PUT` | `/api/v1/system/branches/{id}` |
| `PUT` | `/api/v1/system/branding` |
| `PUT` | `/api/v1/system/company` |
| `PUT` | `/api/v1/system/departments/{id}` |
| `PUT` | `/api/v1/system/localization` |
| `PUT` | `/api/v1/system/notifications` |
| `PUT` | `/api/v1/system/settings` |

### users

| Method | Path |
| --- | --- |
| `GET` | `/api/v1/users/{id}/roles` |
| `PUT` | `/api/v1/users/{id}/roles` |

### vendors

| Method | Path |
| --- | --- |
| `DELETE` | `/api/v1/vendors/categories/{categoryId}` |
| `DELETE` | `/api/v1/vendors/{id}` |
| `DELETE` | `/api/v1/vendors/{id}/contacts/{contactId}` |
| `DELETE` | `/api/v1/vendors/{id}/contracts/{contractId}` |
| `DELETE` | `/api/v1/vendors/{id}/quotations/{quotationId}` |
| `DELETE` | `/api/v1/vendors/{id}/ratings/{ratingId}` |
| `GET` | `/api/v1/vendors` |
| `GET` | `/api/v1/vendors/categories` |
| `GET` | `/api/v1/vendors/contacts` |
| `GET` | `/api/v1/vendors/contracts` |
| `GET` | `/api/v1/vendors/dashboard` |
| `GET` | `/api/v1/vendors/quotations` |
| `GET` | `/api/v1/vendors/quotations/comparison` |
| `GET` | `/api/v1/vendors/{id}` |
| `GET` | `/api/v1/vendors/{id}/contacts` |
| `GET` | `/api/v1/vendors/{id}/contacts/{contactId}` |
| `GET` | `/api/v1/vendors/{id}/contracts` |
| `GET` | `/api/v1/vendors/{id}/contracts/{contractId}` |
| `GET` | `/api/v1/vendors/{id}/financial-references` |
| `GET` | `/api/v1/vendors/{id}/performance` |
| `GET` | `/api/v1/vendors/{id}/products` |
| `GET` | `/api/v1/vendors/{id}/purchase-orders` |
| `GET` | `/api/v1/vendors/{id}/quotations` |
| `GET` | `/api/v1/vendors/{id}/quotations/{quotationId}` |
| `GET` | `/api/v1/vendors/{id}/quotations/{quotationId}/history` |
| `GET` | `/api/v1/vendors/{id}/ratings` |
| `PATCH` | `/api/v1/vendors/{id}/activate` |
| `PATCH` | `/api/v1/vendors/{id}/contacts/{contactId}/emergency` |
| `PATCH` | `/api/v1/vendors/{id}/contacts/{contactId}/primary` |
| `PATCH` | `/api/v1/vendors/{id}/status` |
| `POST` | `/api/v1/vendors` |
| `POST` | `/api/v1/vendors/categories` |
| `POST` | `/api/v1/vendors/{id}/contacts` |
| `POST` | `/api/v1/vendors/{id}/contracts` |
| `POST` | `/api/v1/vendors/{id}/quotations` |
| `POST` | `/api/v1/vendors/{id}/ratings` |
| `PUT` | `/api/v1/vendors/categories/{categoryId}` |
| `PUT` | `/api/v1/vendors/{id}` |
| `PUT` | `/api/v1/vendors/{id}/contacts/{contactId}` |
| `PUT` | `/api/v1/vendors/{id}/contracts/{contractId}` |
| `PUT` | `/api/v1/vendors/{id}/quotations/{quotationId}` |
| `PUT` | `/api/v1/vendors/{id}/ratings/{ratingId}` |

### workflows

| Method | Path |
| --- | --- |
| `DELETE` | `/api/v1/workflows/{id}` |
| `GET` | `/api/v1/workflows` |
| `GET` | `/api/v1/workflows/actions/catalog` |
| `GET` | `/api/v1/workflows/catalog` |
| `GET` | `/api/v1/workflows/dashboard` |
| `GET` | `/api/v1/workflows/executions` |
| `GET` | `/api/v1/workflows/executions/{executionId}` |
| `GET` | `/api/v1/workflows/operators` |
| `GET` | `/api/v1/workflows/triggers/catalog` |
| `GET` | `/api/v1/workflows/{id}` |
| `GET` | `/api/v1/workflows/{id}/executions` |
| `GET` | `/api/v1/workflows/{id}/versions` |
| `GET` | `/api/v1/workflows/{id}/versions/{versionId}` |
| `POST` | `/api/v1/workflows` |
| `POST` | `/api/v1/workflows/executions/{executionId}/cancel` |
| `POST` | `/api/v1/workflows/executions/{executionId}/pause` |
| `POST` | `/api/v1/workflows/executions/{executionId}/resume` |
| `POST` | `/api/v1/workflows/executions/{executionId}/retry` |
| `POST` | `/api/v1/workflows/scheduler/tick` |
| `POST` | `/api/v1/workflows/{id}/clone` |
| `POST` | `/api/v1/workflows/{id}/disable` |
| `POST` | `/api/v1/workflows/{id}/enable` |
| `POST` | `/api/v1/workflows/{id}/pause` |
| `POST` | `/api/v1/workflows/{id}/resume` |
| `POST` | `/api/v1/workflows/{id}/run` |
| `POST` | `/api/v1/workflows/{id}/test` |
| `PUT` | `/api/v1/workflows/{id}` |
