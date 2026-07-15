# API Reference

**Base URL:** `/api/v1`  
**Format:** JSON  
**Auth:** Bearer JWT access token (except public auth routes)  
**Total unique routes (as of 2026-07-15):** 537

This catalog is generated from the live route registrations under `backend/routes` and `backend/src/*/Routes`. For payload field-level contracts, inspect the corresponding controller + DTO/validator in each module.

## 1. Conventions

### 1.1 Versioning

All application APIs are prefixed with `/api/v1`. Breaking changes require a new version path and a migration plan.

### 1.2 HTTP methods

| Method | Usage |
| --- | --- |
| `GET` | Read resource or collection |
| `POST` | Create or command-style actions (`generate`, `sync`, `run`) |
| `PUT` | Full/primary update of a resource |
| `PATCH` | Partial update or status transition |
| `DELETE` | Soft or hard delete per domain rules |

### 1.3 Status codes

| Code | Meaning |
| --- | --- |
| `200` | Success with body |
| `201` | Created |
| `204` | Success without body (e.g. logout) |
| `400` | Malformed request |
| `401` | Missing/invalid/expired authentication |
| `403` | Authenticated but not permitted |
| `404` | Resource not found |
| `422` | Validation / business rule failure |
| `429` | Rate limited |
| `500` | Unexpected server error (includes `meta.trace_id`) |

### 1.4 Success envelopes

**Single resource**

```json
{
  "data": {
    "type": "customers",
    "id": "42",
    "attributes": { }
  }
}
```

**Collection (paginated)**

```json
{
  "data": [ { "type": "customers", "id": "42", "attributes": { } } ],
  "links": {
    "self": "/api/v1/customers?page[number]=1&page[size]=25",
    "first": "...",
    "last": "..."
  },
  "meta": {
    "current_page": 1,
    "per_page": 25,
    "total": 100,
    "last_page": 4
  }
}
```

Pagination query parameters (supported variants):

- Preferred: `page[number]`, `page[size]`
- Legacy aliases still accepted in several modules: `page`, `per_page`

### 1.5 Error envelope

```json
{
  "errors": [
    {
      "status": "422",
      "code": "validation_error",
      "title": "Unprocessable Entity",
      "detail": "The email has already been taken.",
      "source": { "pointer": "/data/attributes/email" }
    }
  ]
}
```

Headers of interest:

- `X-Trace-Id` — correlate client, gateway, and API logs
- `Authorization: Bearer <accessToken>`
- Cookie: refresh token (HttpOnly; not readable by JS)

## 2. Authentication

| Method | Path | Auth | Notes |
| --- | --- | --- | --- |
| `POST` | `/api/v1/auth/login` | Public (rate limited) | Body: `email`, `password`, optional `rememberMe` |
| `POST` | `/api/v1/auth/refresh` | Refresh cookie | Rotates refresh cookie; returns new access token |
| `POST` | `/api/v1/auth/logout` | Cookie / session | Revokes refresh; `204` |
| `POST` | `/api/v1/auth/forgot-password` | Public (rate limited) | Initiates reset flow |
| `POST` | `/api/v1/auth/reset-password` | Public (rate limited) | Completes reset with token |
| `POST` | `/api/v1/auth/change-password` | Authenticated | Authenticated password change |

Login/refresh success attributes include `accessToken` and a safe `user` object. Refresh tokens are **never** returned in JSON.

## 3. RBAC and identity

| Method | Path |
| --- | --- |
| `GET` | `/api/v1/me/permissions` |
| `GET` | `/api/v1/roles` |
| `POST` | `/api/v1/roles` |
| `GET` | `/api/v1/roles/{id}` |
| `PUT` | `/api/v1/roles/{id}` |
| `DELETE` | `/api/v1/roles/{id}` |
| `GET` | `/api/v1/permissions` |
| `GET` | `/api/v1/users/{id}/roles` |
| `PUT` | `/api/v1/users/{id}/roles` |

## 4. Dashboard

| Method | Path |
| --- | --- |
| `GET` | `/api/v1/dashboard` |

## 5. Customers

| Method | Path |
| --- | --- |
| `GET` | `/api/v1/customers` |
| `POST` | `/api/v1/customers` |
| `GET` | `/api/v1/customers/{id}` |
| `PUT` | `/api/v1/customers/{id}` |
| `DELETE` | `/api/v1/customers/{id}` |
| `PATCH` | `/api/v1/customers/{id}/status` |

Typical permissions: `customers.view`, `customers.create`, `customers.update`, `customers.delete`.

## 6. Packages

Routes registered from `backend/src/Packages/Routes/packages.php` under `/api/v1/packages` (CRUD, categories, pricing, bandwidth/network/billing profiles as implemented).

## 7. Connections

| Method | Path |
| --- | --- |
| `GET` | `/api/v1/connections` |
| `POST` | `/api/v1/connections` |
| `GET` | `/api/v1/connections/{id}` |
| `PUT` | `/api/v1/connections/{id}` |
| `DELETE` | `/api/v1/connections/{id}` |
| `PATCH` | `/api/v1/connections/{id}/activate` |
| `PATCH` | `/api/v1/connections/{id}/suspend` |
| `PATCH` | `/api/v1/connections/{id}/disconnect` |
| `PATCH` | `/api/v1/connections/{id}/transfer` |

## 8. Billing (invoices)

| Method | Path |
| --- | --- |
| `GET` | `/api/v1/invoices` |
| `POST` | `/api/v1/invoices` |
| `GET` | `/api/v1/invoices/statistics` |
| `GET` | `/api/v1/invoices/{id}` |
| `PUT` | `/api/v1/invoices/{id}` |
| `DELETE` | `/api/v1/invoices/{id}` |
| `PATCH` | `/api/v1/invoices/{id}/status` |
| `POST` | `/api/v1/invoices/generate` |
| `POST` | `/api/v1/invoices/bulk-generate` |
| `GET` | `/api/v1/invoices/{id}/activity` |

Invoice status machine (service-enforced): draft → pending/issued → partially_paid/paid/overdue → cancelled/void. Finalized invoices are immutable.

## 9. Payments

| Method | Path |
| --- | --- |
| `GET` | `/api/v1/payments` |
| `POST` | `/api/v1/payments` |
| `POST` | `/api/v1/payments/receive` |
| `POST` | `/api/v1/payments/bulk` |
| `GET` | `/api/v1/payments/statistics` |
| `GET` | `/api/v1/payments/lookups` |
| `GET` | `/api/v1/payments/methods` |
| `GET` | `/api/v1/payments/accounts` |
| `GET` | `/api/v1/payments/export` |
| `GET` | `/api/v1/payments/{id}` |
| `PUT` | `/api/v1/payments/{id}` |
| `DELETE` | `/api/v1/payments/{id}` |
| `POST` | `/api/v1/payments/{id}/allocate` |
| `POST` | `/api/v1/payments/{id}/reverse` |
| `POST` | `/api/v1/payments/{id}/refund` |
| `GET` | `/api/v1/payments/{id}/allocations` |
| `GET` | `/api/v1/payments/{id}/activity` |
| `GET` | `/api/v1/payments/{id}/receipt` |
| `GET` | `/api/v1/payments/{id}/receipt/pdf` |

## 10. Finance

| Method | Path |
| --- | --- |
| `GET` | `/api/v1/finance/dashboard` |
| `GET`/`POST` | `/api/v1/finance/accounts` |
| `GET`/`POST` | `/api/v1/finance/chart-of-accounts` |
| `GET`/`POST` | `/api/v1/finance/expenses` |
| `GET`/`POST` | `/api/v1/finance/revenue` |
| `GET`/`POST` | `/api/v1/finance/journal-entries` |
| `GET` | `/api/v1/finance/ledger` |

## 11. MikroTik

| Method | Path |
| --- | --- |
| `GET`/`POST` | `/api/v1/mikrotik/routers` |
| `GET`/`PUT`/`DELETE` | `/api/v1/mikrotik/routers/{id}` |
| `PATCH` | `/api/v1/mikrotik/routers/{id}/enable` |
| `PATCH` | `/api/v1/mikrotik/routers/{id}/disable` |
| `PUT` | `/api/v1/mikrotik/routers/{id}/tags` |
| `POST` | `/api/v1/mikrotik/test-connection` |
| `POST` | `/api/v1/mikrotik/routers/{id}/test-connection` |
| `POST` | `/api/v1/mikrotik/routers/{id}/discover` |
| `GET` | `/api/v1/mikrotik/routers/{id}/health` |
| `POST` | `/api/v1/mikrotik/routers/{id}/health/check` |
| `GET` | `/api/v1/mikrotik/routers/{id}/statistics` |
| CRUD | `/api/v1/mikrotik/router-groups`, `/api/v1/mikrotik/router-tags` |

Router passwords are encrypted at rest; never log credentials.

## 12. PPPoE

Routes under `/api/v1/pppoe/*` for accounts, session history, sync, and auth logs (`backend/src/Pppoe/Routes/pppoe.php`).

## 13. Hotspot

Major resource groups under `/api/v1/hotspot/`:

- `profiles` — CRUD
- `users` — CRUD, enable/disable, suspend/resume, password reset, profile/router assignment, bulk import
- `vouchers` — generate, list, revoke, batch print, stats
- `sessions` — active, disconnect, force logout, history, login history
- `sync` — per router/user, detect-missing, repair, import, logs

## 14. Infrastructure

| Resource | Base path |
| --- | --- |
| Dashboard | `GET /api/v1/infrastructure/dashboard` |
| POP sites | `/api/v1/infrastructure/pop-sites` |
| Towers | `/api/v1/infrastructure/towers` |
| Sectors | `/api/v1/infrastructure/sectors` |
| Devices | `/api/v1/infrastructure/devices` |

Each supports list/create/show/update/delete and status patches; nested helpers for map points, coverage, and related collections.

## 15. Monitoring

Routes under `/api/v1/monitoring/*` for device status, alerts, interface snapshots, and sync events (`backend/src/Monitoring/Routes/monitoring.php`).

## 16. Support

Ticket lifecycle, teams, categories, SLA policies, comments, and assignments under `/api/v1/support/*` (`backend/src/Support/Routes/support.php`).

## 17. Inventory

| Area | Paths |
| --- | --- |
| Dashboard / search / lookups | `/api/v1/inventory/dashboard`, `/search`, `/lookups/{resource}` |
| Products | `/api/v1/inventory/products` |
| Warehouses + locations | `/api/v1/inventory/warehouses`, `.../locations` |
| Stock + movements | `/api/v1/inventory/stock`, `/stock-movements` |
| Transfers | `/api/v1/inventory/transfers` |
| Assets | `/api/v1/inventory/assets` (+ assign/return/timeline) |
| Accounting | `/api/v1/inventory/settings/accounting`, `/finance-postings` |

## 18. Purchasing

Purchase requests, purchase orders, goods receipts, supplier invoices, and finance postings under `/api/v1/purchasing/*`.

## 19. Vendors

| Method | Path patterns |
| --- | --- |
| CRUD | `/api/v1/vendors`, `/api/v1/vendors/{id}` |
| Status | `PATCH .../activate`, `PATCH .../status` |
| Nested | contacts, contracts, quotations, ratings |
| Catalog | categories, contacts (global), contracts, quotations (+ comparison) |
| Insights | dashboard, performance, products, purchase-orders, financial-references |

## 20. Field service

| Area | Paths |
| --- | --- |
| Dashboard / lookups | `/api/v1/field-service/dashboard`, `/lookups/{resource}` |
| Technicians | `/api/v1/field-service/technicians` |
| Schedule | `/api/v1/field-service/schedule`, `/schedule/unscheduled` |
| Installation requests | `/api/v1/field-service/installation-requests` |
| Work orders | `/api/v1/field-service/work-orders` (+ timeline) |

## 21. Reports

| Method | Path |
| --- | --- |
| `GET` | `/api/v1/reports/catalog` |
| `GET` | `/api/v1/reports/filters` |
| `POST` | `/api/v1/reports/generate` |
| `GET` | `/api/v1/reports/dashboards/{dashboard}` |
| CRUD + run | `/api/v1/reports/saved` |
| CRUD | `/api/v1/reports/templates` |
| CRUD | `/api/v1/reports/schedules` |
| Export lifecycle | `/api/v1/reports/exports` |

## 22. System administration

| Area | Paths |
| --- | --- |
| Dashboard / configuration | `/api/v1/system/dashboard`, `/configuration` |
| Company | `GET`/`PUT /api/v1/system/company` |
| Branches | CRUD + activate/deactivate |
| Departments | CRUD + activate/deactivate |
| Settings | show/update + maintenance enable/disable |
| Branding | show/update + asset upload |
| Localization | show/update + options |
| Notification prefs | show/update |

## 23. Notifications

| Area | Paths |
| --- | --- |
| Inbox | `/api/v1/notifications`, read/archive/delete, unread-count, read-all |
| Catalog / dispatch | `/catalog`, `/dispatch` |
| Preferences | `/preferences` |
| Templates | CRUD + preview |
| Deliveries / events | list + show |

## 24. Audit and compliance

| Area | Paths |
| --- | --- |
| Logs | `/api/v1/audit/logs`, filter-options, resource-history, dashboard |
| Activity | `/api/v1/audit/activity`, `/users/{id}/activity` |
| Exports | `POST /audit/export`, list + download |
| Compliance policies | `/api/v1/compliance/policies` |
| Retention policies | `/api/v1/compliance/retention` |

## 25. Backup and DR

| Area | Paths |
| --- | --- |
| Jobs / stats / files | `/api/v1/backup/jobs`, `/statistics`, `/files` (+ verify) |
| Schedules | CRUD-ish under `/backup/schedules` |
| Restore | `/backup/restore/history`, `/restore/execute` |
| Storage providers | list/create/update |
| DR plans | list/show/update |

## 26. Integration platform

| Area | Paths |
| --- | --- |
| Dashboard | `/api/v1/integration/dashboard` |
| Client applications | `/integration/applications` |
| API keys | CRUD + regenerate |
| Webhooks | CRUD + rotate-secret, test, inbound |
| Deliveries | list/show/retry |
| Events / request logs | list/show |
| Connectors | list/show/update/test |

## 27. Workflows

| Area | Paths |
| --- | --- |
| Dashboard / catalogs | `/workflows/dashboard`, `/catalog`, triggers/actions/operators |
| Definitions | CRUD `/workflows` |
| Lifecycle | enable, disable, pause, resume, clone, run, test |
| Versions | `/workflows/{id}/versions` |
| Executions | list/show/retry/cancel/pause/resume |
| Scheduler | `POST /workflows/scheduler/tick` |

## 28. Customer portal

Customer-scoped routes under `/api/v1/portal/*` (JWT user linked via `users.customer_id`):

| Area | Paths |
| --- | --- |
| Dashboard / connection | portal dashboard and connection details |
| Invoices / payments | list and pay actions |
| Tickets | list, create, show, reply, close-request |
| Notifications | list, read, archive |
| Preferences / profile | get/update |

Portal UI is served from the SPA at `/portal/*`.

## 29. Operational endpoints

| Method | Path | Purpose |
| --- | --- | --- |
| `GET` | `/healthz` | Liveness (gateway / process) |
| `GET` | `/readyz` | Readiness (DB + storage) |

## 30. How to extend the API safely

1. Add or update the module route file under `backend/src/{Module}/Routes/`.
2. Register it from `backend/routes/api.php` if it is a new module.
3. Add controller method + DTO validation + service method + repository SQL.
4. Authorize with an existing or newly seeded permission string.
5. Return `ApiResponse` shapes only.
6. Add PHPUnit coverage for status transitions and validation failures.
7. Update this reference’s module section in the same PR when routes change.

## 31. Full route catalog

For the complete method + path listing of all **537** registered routes, see:

**[02-API_ROUTE_CATALOG.md](./02-API_ROUTE_CATALOG.md)**

## 32. Source of truth

- Route registration hub: `backend/routes/api.php`
- Auth: `backend/routes/auth.php`
- RBAC: `backend/routes/rbac.php`
- Dashboard: `backend/routes/dashboard.php`
- Module routes: `backend/src/*/Routes/*.php`
