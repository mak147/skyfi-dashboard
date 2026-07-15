# Architecture Guide

**Project:** SkyFi ISP Management System  
**Document:** Developer Architecture Guide (as-built)  
**Date:** 2026-07-15

## 1. Purpose

This guide describes the **implemented** architecture of SkyFi. It is the authoritative onboarding and design reference for engineers working in this repository. Historical design docs under `/docs/Document *.md` remain useful for product intent; when they conflict with the code, **the code and this guide win**.

## 2. Architectural style

SkyFi is a **3-tier modular monolith**:

```text
┌─────────────────────────────┐
│  Presentation tier          │  React 18 SPA (staff admin + customer portal)
│  Vite / TypeScript / Tailwind
└──────────────┬──────────────┘
               │ HTTPS JSON REST  (/api/v1/*)
┌──────────────▼──────────────┐
│  Application tier           │  PHP modular monolith (FPM)
│  Router → Middleware →      │
│  Controller → Service →     │
│  Repository → PDO           │
└──────────────┬──────────────┘
               │
     ┌─────────┴──────────┐
     ▼                    ▼
 MariaDB / MySQL         Redis
 (system of record)      (cache / coordination)
```

### Why modular monolith

- Single deployable unit (Compose stack: Nginx + PHP-FPM + Supervisor + MariaDB + Redis).
- Business domains isolated under `backend/src/{Module}` with contracts.
- Clear future extraction path (e.g., Billing) without premature microservice cost.
- Phase 1 audit confirmed this is the correct shape for v1.

## 3. Backend architecture

### 3.1 Entry point and request lifecycle

1. Web root: `backend/public/index.php`
2. Composer / fallback autoload loads `SkyFi\` from `backend/src/`
3. Environment and config: `Environment::load`, `config/app.php`, `config/database.php`, `config/cors.php`
4. `TraceIdMiddleware` assigns `X-Trace-Id`
5. CORS headers applied; `OPTIONS` short-circuits with `204`
6. `Container` composition root builds services
7. `routes/api.php` registers all module route files
8. `Router::dispatch` matches method + path
9. Middleware (`JwtAuthMiddleware`, rate limits, permission checks) run as wrappers
10. Controllers return `Response` / `ApiResponse`
11. `SecurityHeadersMiddleware` applied before send
12. Uncaught exceptions → `ApiResponse::error` + structured `JsonLogger`

Health endpoints (outside the main router bootstrap path, served as static/public scripts or gateway targets):

- `GET /healthz` — process liveness
- `GET /readyz` — DB connectivity and writable storage

### 3.2 Layer responsibilities

| Layer | Responsibility | Must not |
| --- | --- | --- |
| **Routes** | Map HTTP method/path to controller callables; wire middleware | Contain business rules |
| **Controllers** | Authz, parse query/body into DTOs, call services, shape JSON:API-like responses | Own SQL or multi-step domain rules |
| **DTOs / Data / Validators** | Normalize and validate untrusted input | Persist data |
| **Services** | Orchestrate use cases, transactions, status machines, cross-repo work | Emit raw HTTP |
| **Repositories** | SQL via PDO, hydration to domain models | Call other modules’ controllers |
| **Contracts** | Interfaces for DI and testing | Leak infrastructure types unnecessarily |
| **Shared** | Auth, HTTP kernel, exceptions, logging, pagination, events | Accumulate domain logic |

### 3.3 Module boundaries

Backend modules live under `backend/src/`:

| Module | Domain |
| --- | --- |
| `Shared` | Kernel: Auth, HTTP, Config, Exceptions, Logging, Providers, Events |
| `Rbac` | Roles, permissions, user role assignment |
| `Dashboard` | Operator dashboard aggregates |
| `Customers` | CRM customer records and lifecycle |
| `Packages` | Service packages and pricing profiles |
| `Connections` | Customer service connections |
| `Billing` | Invoices, generation, status transitions |
| `Payments` | Payments, allocations, receipts, refunds |
| `Finance` | COA, ledger, journals, expenses, revenue |
| `Mikrotik` | Router inventory, discovery, health (RouterOS API) |
| `Pppoe` | PPPoE account provisioning and sessions |
| `Hotspot` | Profiles, users, vouchers, sessions, router sync |
| `Infrastructure` | POP sites, towers, sectors, network devices |
| `Monitoring` | Device status, alerts, interface snapshots |
| `Support` | Tickets, teams, SLA, assignments |
| `Inventory` | Products, warehouses, stock, assets |
| `Purchasing` | PR/PO, goods receipt, supplier invoices |
| `Vendors` | Supplier CRM (contacts, contracts, quotations, ratings) |
| `FieldService` | Technicians, installation requests, work orders |
| `Reports` | Report catalog, saved reports, schedules, exports |
| `System` | Company, branches, departments, branding, settings |
| `Notifications` | In-app notifications, templates, deliveries |
| `Audit` | Audit logs, activity, compliance, retention |
| `Backup` | Backup jobs, schedules, restore, DR plans |
| `Integration` | API keys, webhooks, connectors, event registry |
| `Workflow` | Automation rules, executions, catalogs |
| `Portal` | Customer self-service API surface |

**Rule:** Modules communicate through contracts/events. Do not instantiate another module’s concrete repository from outside its package.

### 3.4 Dependency injection

- Composition root: `SkyFi\Shared\Providers\Container`
- Controllers and services resolve interfaces (`*Contract`) to PDO-backed implementations
- Route files call `$container->get(SomeController::class)` and wrap handlers
- Prefer constructor injection and `readonly` properties
- Phase 1 notes: the container is large; extract providers incrementally—do not rewrite atomically

### 3.5 Authentication and authorization

**Authentication**

- Access: short-lived HS256 JWT (`Authorization: Bearer …`), memory-only on the SPA
- Refresh: opaque token, SHA-256 hashed at rest, rotated on use, `HttpOnly; Secure; SameSite=Strict` cookie
- Endpoints: `/api/v1/auth/login|refresh|logout|forgot-password|reset-password|change-password`
- Login/forgot/reset are rate-limited (`RateLimitMiddleware` + `api_rate_limits` table)

**Authorization**

- JWT middleware attaches claims to the request
- Controllers/services call `RequirePermissionMiddleware` with permission strings (e.g. `customers.view`)
- Effective permissions: `GET /api/v1/me/permissions`
- Super-admin style wildcard `*` is supported in permission checks on the frontend

### 3.6 API response contract

JSON:API-inspired envelopes via `SkyFi\Shared\Http\ApiResponse`:

- Single resource: `{ data: { type, id, attributes } }`
- Collection: `{ data: [...], links, meta }`
- Errors: `{ errors: [{ status, code, title, detail, source? }] }`
- Trace ID on response headers and 500 meta

### 3.7 Persistence

- Raw SQL migrations in `backend/database/migrations/*.sql`
- Runner: `php database/migrate.php` (`--pretend` supported)
- Seeder: `php database/seed.php` (roles/permissions/admin via env)
- PDO only; no full ORM
- Soft deletes (`deleted_at`) on financially or legally sensitive entities
- Money: `DECIMAL(12,2)` (or equivalent)—never float

## 4. Frontend architecture

### 4.1 Application shell

- Entry: `frontend/src/main.tsx`
- Providers: `AppProvider` (Redux, React Query, auth bootstrap)
- Router: `frontend/src/routes/index.tsx` with lazy-loaded feature routes
- Staff UI under `ProtectedRoute` + `AppLayout`
- Customer portal under `/portal/*` (`frontend/src/portal`)

### 4.2 Feature modules

Features under `frontend/src/features/{name}/` typically include:

```text
api/          # TanStack Query hooks and API calls
components/   # Feature-local UI
pages/        # Route-level screens
routes/       # Nested React Router definitions (where used)
types.ts      # Feature types (optional)
```

Shared UI lives in `components/ui` and `components/common`. Shared hooks: `hooks/useAuth`, `hooks/usePermissions`.

### 4.3 State management

| Concern | Tool |
| --- | --- |
| Auth session (access token user, init flags) | Redux Toolkit `authSlice` |
| Server data (lists, details, mutations) | TanStack Query |
| Forms | React Hook Form + Zod |
| Ephemeral UI (modals, filters) | Local component state |

Do **not** put server entities into Redux. Do **not** put access tokens in `localStorage`.

### 4.4 API client

`frontend/src/lib/apiClient.ts`:

- Axios instance with `baseURL = VITE_API_BASE_URL` (default `/api/v1` behind Nginx)
- `withCredentials: true` for refresh cookie
- Request interceptor attaches Bearer access token and `X-Trace-Id`
- Response interceptor: on `401` / token expiry, single-flight refresh, retry original request; on refresh failure, force re-login

## 5. Cross-cutting concerns

| Concern | Implementation |
| --- | --- |
| Logging | `JsonLogger` with secret scrubbing |
| Errors | Domain exceptions → `AppException` hierarchy → `ApiResponse::error` |
| Pagination | Shared `PaginationInput` (JSON:API `page[number]`/`page[size]` + legacy aliases) |
| Security headers | `SecurityHeadersMiddleware` (stricter in production) |
| MikroTik secrets | XChaCha20-Poly1305 with `MIKROTIK_CREDENTIAL_ENCRYPTION_KEY` |
| Background work | Supervisor container for operational workers / scheduled ticks |
| Observability hooks | Trace IDs; audit module; monitoring module |

## 6. Deployment architecture (summary)

Production Compose (`docker-compose.prod.yml`):

| Service | Role |
| --- | --- |
| `nginx` | SPA static assets + reverse proxy `/api/*` + `/healthz` `/readyz` |
| `backend` | PHP-FPM application |
| `supervisor` | Workers / heartbeat processes |
| `mariadb` | Primary database |
| `redis` | Cache and coordination |

Details: [Deployment Guide](./11-DEPLOYMENT_GUIDE.md) and `docs/deployment/DEPLOYMENT_GUIDE.md`.

## 7. Testing architecture

| Layer | Tool | Location |
| --- | --- | --- |
| Backend unit/service | PHPUnit 10 | `backend/tests/Unit`, `Integration`, `Feature` |
| Frontend unit | Vitest + RTL | `frontend/src/**/*.test.*` |
| E2E | Playwright | `frontend/e2e/*.spec.ts` |

See root `TESTING.md` and [Local Development Guide](./10-LOCAL_DEVELOPMENT.md).

## 8. Evolution principles

1. Prefer additive API changes; version breaking changes under `/api/v2` only with an approved plan.
2. Keep controllers thin; grow services and repositories with tests.
3. Extract Container providers module-by-module rather than a big-bang DI rewrite.
4. Do not introduce new global state managers without architecture review.
5. Do not implement new business features unless explicitly requested during production-readiness phases.

## 9. References

- Phase 1: `docs/production-readiness/ARCHITECTURE_AUDIT.md`
- Original design: `docs/Document 05 Software Architecture.md`, `Document 06 System Architecture.md`
- API details: [API Reference](./02-API_REFERENCE.md)
- Modules: [Module Documentation](./05-MODULE_DOCUMENTATION.md)
