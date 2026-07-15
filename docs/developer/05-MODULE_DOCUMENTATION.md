# Module Documentation

SkyFi is organized as a modular monolith. Each backend module under `backend/src/{Module}` maps to one or more frontend features under `frontend/src/features/{feature}` (or `frontend/src/portal` for the customer portal).

## 1. Module map

| Backend module | Frontend feature(s) | Primary API prefix | Core responsibility |
| --- | --- | --- | --- |
| `Shared` | `authentication`, shared libs | `/api/v1/auth` | Kernel, JWT auth, HTTP, logging |
| `Rbac` | `rbac` | `/api/v1/roles`, `/permissions`, `/me` | Roles and permissions |
| `Dashboard` | `dashboard` | `/api/v1/dashboard` | Operator KPIs |
| `Customers` | `customers` | `/api/v1/customers` | Customer CRM lifecycle |
| `Packages` | `packages` | `/api/v1/packages` | Service catalog and profiles |
| `Connections` | `connections` | `/api/v1/connections` | Service connections |
| `Billing` | `billing` | `/api/v1/invoices` | Invoicing and generation |
| `Payments` | `payments` | `/api/v1/payments` | Payments and receipts |
| `Finance` | `finance` | `/api/v1/finance` | Ledger and operating finance |
| `Mikrotik` | `mikrotik` | `/api/v1/mikrotik` | Router inventory and health |
| `Pppoe` | `pppoe` | `/api/v1/pppoe` | PPPoE accounts and sessions |
| `Hotspot` | `hotspot` | `/api/v1/hotspot` | Hotspot users/vouchers/sync |
| `Infrastructure` | `infrastructure` | `/api/v1/infrastructure` | POP/tower/sector/devices |
| `Monitoring` | (monitoring UI under network/ops features as wired) | `/api/v1/monitoring` | Alerts and device telemetry |
| `Support` | `support` | `/api/v1/support` | Helpdesk tickets |
| `Inventory` | `inventory` | `/api/v1/inventory` | Stock, warehouses, assets |
| `Purchasing` | `purchasing` | `/api/v1/purchasing` | PR/PO/GRN procurement |
| `Vendors` | `vendors` | `/api/v1/vendors` | Supplier management |
| `FieldService` | `field-service` | `/api/v1/field-service` | Installations and work orders |
| `Reports` | `reports` | `/api/v1/reports` | BI reports and exports |
| `System` | `system` | `/api/v1/system` | Company and system settings |
| `Notifications` | `notifications` | `/api/v1/notifications` | Notification center |
| `Audit` | `audit` | `/api/v1/audit`, `/compliance` | Audit and compliance |
| `Backup` | `backup` | `/api/v1/backup` | Backup/restore/DR |
| `Integration` | `integration` | `/api/v1/integration` | API keys, webhooks, connectors |
| `Workflow` | `workflow` | `/api/v1/workflows` | Automation engine |
| `Portal` | `portal` (`frontend/src/portal`) | `/api/v1/portal` | Customer self-service |

## 2. Standard backend module layout

```text
backend/src/{Module}/
├── Contracts/       # Interfaces (*Contract)
├── Controllers/     # HTTP adapters
├── Data|DTOs/       # Input DTOs (naming varies by module age)
├── DomainModels|Models/
├── Repositories/    # PDO implementations
├── Routes/          # Route registrar closure
├── Services/        # Business orchestration
└── Validators/      # Optional dedicated validators
```

### Request path inside a module

```text
Route → JwtAuthMiddleware → Controller
  → RequirePermissionMiddleware
  → DTO::fromRequest / validator
  → Service use case
  → Repository SQL
  → Domain model
  → ApiResponse envelope
```

## 3. Module deep dives

### 3.1 Shared / Authentication

- **Path:** `backend/src/Shared/Auth`
- **Controllers:** `AuthController`
- **Services:** `AuthService`, `JwtTokenService`
- **Repos:** users, refresh tokens, password resets
- **Frontend:** `features/authentication`, `hooks/useAuth`, `store/authSlice`, `lib/apiClient`
- **Notes:** Access token in memory only; refresh cookie rotation; rate limits on login/forgot/reset.

### 3.2 RBAC

- **Path:** `backend/src/Rbac`
- **Routes:** `backend/routes/rbac.php`
- **Frontend:** `/admin/roles/*`
- **Notes:** Permission strings are dotted (`billing.view`). Frontend `usePermissions` supports wildcard `*`.

### 3.3 Customers

- **Path:** `backend/src/Customers`
- **Status machine:** lead → prospect → active → suspended → disconnected → archived (invalid jumps rejected)
- **Frontend pages:** list, create, detail, edit
- **Tests:** `backend/tests/Unit/Customers`

### 3.4 Billing

- **Path:** `backend/src/Billing`
- **Key ops:** create invoice, generate, bulk-generate, status transitions, activity trail
- **Frontend:** invoices list/detail, generate, bulk billing, history
- **Tests:** `backend/tests/Unit/Billing`

### 3.5 Payments

- Receive, allocate, reverse, refund, receipt/PDF export
- Integrates with invoices and customer credit ledger

### 3.6 Finance

- Chart of accounts, financial accounts, journals, ledger, expenses, revenue, dashboard aggregates
- Must keep double-entry integrity in service layer

### 3.7 MikroTik + PPPoE + Hotspot

- **MikroTik:** inventory, groups/tags, TLS API test, discovery, health snapshots (credential encryption required)
- **PPPoE:** account lifecycle, session/auth/sync logs
- **Hotspot:** profiles, users, vouchers, active sessions, router sync/import/repair

### 3.8 Infrastructure + Monitoring

- Physical network model: POP → Tower → Sector → Device
- Monitoring captures status history, interface snapshots, alerts

### 3.9 Support

- Teams, categories, SLA, tickets, comments, assignments, history

### 3.10 Inventory / Purchasing / Vendors

- Inventory owns products, stock, warehouses, assets, transfers, finance postings
- Purchasing owns PR → PO → GRN → supplier invoice
- Vendors module extends supplier CRM (contacts, contracts, quotations, ratings) on top of vendor master data

### 3.11 Field service

- Technicians, schedules, installation requests, work orders, materials, visit logs

### 3.12 Reports

- Catalog + generate, saved reports, templates, schedules, export history
- Export may produce spreadsheet/PDF artifacts via PHP libraries

### 3.13 System

- Company profile, multi-branch, departments, branding, localization, maintenance mode flags

### 3.14 Notifications

- Event catalog, templates, user inbox, delivery history, preferences, dispatch API

### 3.15 Audit

- Immutable-style audit log queries, activity feed, compliance and retention policies, export jobs

### 3.16 Backup

- Storage providers, schedules, manual jobs, file verification, restore history, DR plans

### 3.17 Integration

- Client applications, API keys, outbound webhooks + deliveries, inbound webhook endpoint, connectors, request logs, event registry

### 3.18 Workflow

- Trigger/condition/action definitions, versioning, enable/disable, manual run/test, execution control (retry/cancel/pause/resume), scheduler tick

### 3.19 Portal

- Customer-facing subset of billing, connection, tickets, notifications, profile
- Frontend isolated under `frontend/src/portal` with dedicated routes/layout

## 4. Frontend feature conventions

| Concern | Pattern |
| --- | --- |
| Data fetching | TanStack Query hooks in `features/*/api` |
| Mutations | `useMutation` with cache invalidation of list/detail keys |
| Auth-aware UI | `useAuth`, `usePermissions` |
| Forms | React Hook Form + Zod schemas |
| Routing | Lazy feature route modules mounted in `routes/index.tsx` |
| UI primitives | `components/ui/*` |
| Layout | `layouts/AppLayout.tsx` for staff shell |

## 5. Cross-module integration points

| From | To | Mechanism |
| --- | --- | --- |
| Billing | Customers / Connections / Packages | IDs + repository reads via contracts/services |
| Payments | Billing / Finance | Allocations and financial events |
| Inventory / Purchasing | Finance | finance posting tables |
| Hotspot / PPPoE | MikroTik | Router credentials + API adapters |
| Portal | Customers / Billing / Support / Notifications | Scoped services using `customer_id` |
| Workflow | Many modules | Trigger catalog + action executors |
| Integration | Domain events | Event registry + webhook deliveries |
| Audit | All modules | Logging of actor/resource mutations |

## 6. Adding a new module (checklist)

Only when product work is explicitly requested:

1. Create `backend/src/{Module}/` with Contracts, Controllers, Services, Repositories, Routes.
2. Register routes in `backend/routes/api.php`.
3. Bind implementations in `Container` (or a dedicated provider once extracted).
4. Add SQL migration(s) and seed permissions.
5. Add frontend feature folder + lazy routes.
6. Add unit tests; extend E2E for critical flows.
7. Update developer docs (this file, API reference, ER diagrams as needed).

## 7. Source anchors

- Backend modules: `backend/src/*`
- Frontend features: `frontend/src/features/*`
- Portal: `frontend/src/portal/*`
- Route hub: `backend/routes/api.php`
- SPA route hub: `frontend/src/routes/index.tsx`
