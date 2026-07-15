# SkyFi ISP Management System — Release Notes v1.0.0

**Release date:** 2026-07-15  
**Version:** 1.0.0  
**Codename:** Production Foundation  
**Status:** General Availability

---

## Overview

SkyFi v1.0.0 is the first production release of the SkyFi Networks ISP Management System — an enterprise-grade, modular monolith platform that unifies customer management, billing, payments, finance, network provisioning, inventory, field service, support, reporting, automation, and a customer self-service portal into a single deployable stack.

This release represents the culmination of a complete functional development cycle followed by a six-phase production-readiness program covering architecture optimization, automated testing, deployment infrastructure, developer documentation, operations documentation, and this final release preparation.

---

## Platform at a glance

| Metric | Value |
| --- | --- |
| Backend modules | 26 domain modules + Shared kernel |
| Frontend features | 25 feature modules + Customer Portal |
| API endpoints | 537 registered REST routes under `/api/v1` |
| Database tables | 165 application tables across 33 SQL migrations |
| Backend PHP source | ~57,000 lines across ~800 files |
| Frontend TypeScript/TSX source | ~29,000 lines across ~480 files |
| Contracts (interfaces) | 134 |
| Controllers | 96 |
| Services | 92 |
| Repositories | 85 |
| Validators | 51 |
| RBAC permissions | 80+ granular permission strings |
| RBAC roles | 10 predefined roles (Super Administrator through Customer) |
| PHPUnit test suites | 28 unit test files |
| Frontend unit/integration tests | 5 test modules (hooks, store, routes, components) |
| E2E test scenarios | 6 Playwright spec files (auth, RBAC, billing, finance, portal, workflow) |

---

## Functional modules

### Core business

| Module | API prefix | Description |
| --- | --- | --- |
| **Authentication** | `/api/v1/auth` | JWT access tokens, HttpOnly rotating refresh cookies, password reset, change password, rate-limited auth endpoints |
| **RBAC** | `/api/v1/roles`, `/permissions`, `/me` | Role-based access control with 10 predefined roles and 80+ permission strings including wildcard super-admin |
| **Dashboard** | `/api/v1/dashboard` | Operator KPI aggregates |
| **Customers** | `/api/v1/customers` | Customer CRM lifecycle (lead → prospect → active → suspended → disconnected → archived) with enforced status transitions |
| **Packages** | `/api/v1/packages` | Service catalog, pricing profiles, bandwidth profiles, network profiles, billing settings |
| **Connections** | `/api/v1/connections` | Customer service connections with activate/suspend/disconnect/transfer operations |
| **Billing** | `/api/v1/invoices` | Invoice CRUD, generation, bulk generation, status transitions (draft → pending → issued → paid → void), activity trail |
| **Payments** | `/api/v1/payments` | Payment receipt, allocation, reversal, refund, receipt/PDF export |
| **Finance** | `/api/v1/finance` | Chart of accounts, financial accounts, journals, ledger, expenses, revenue, dashboard aggregates |

### Network operations

| Module | API prefix | Description |
| --- | --- | --- |
| **MikroTik** | `/api/v1/mikrotik` | Router inventory, groups/tags, TLS API test, discovery, health snapshots; credentials encrypted with XChaCha20-Poly1305 |
| **PPPoE** | `/api/v1/pppoe` | PPPoE account lifecycle, session management, sync audit/repair/import |
| **Hotspot** | `/api/v1/hotspot` | Profiles, users, vouchers, active sessions, router sync/import/repair |
| **Infrastructure** | `/api/v1/infrastructure` | POP sites → Towers → Sectors → Network Devices physical network model |
| **Monitoring** | `/api/v1/monitoring` | Device status history, interface snapshots, alert management |

### Operations & support

| Module | API prefix | Description |
| --- | --- | --- |
| **Support** | `/api/v1/support` | Tickets, teams, categories, SLA, assignments, comments, timeline |
| **Inventory** | `/api/v1/inventory` | Products, warehouses, stock, assets, transfers, finance postings |
| **Purchasing** | `/api/v1/purchasing` | Purchase Requests → Purchase Orders → Goods Receipts → Supplier Invoices |
| **Vendors** | `/api/v1/vendors` | Supplier CRM with contacts, contracts, quotations, ratings, categories |
| **Field Service** | `/api/v1/field-service` | Technicians, installation requests, work orders, schedules, visit logs |
| **Reports** | `/api/v1/reports` | Report catalog, saved reports, templates, schedules, export to spreadsheet/PDF |

### Platform & automation

| Module | API prefix | Description |
| --- | --- | --- |
| **System** | `/api/v1/system` | Company profile, multi-branch, departments, branding, localization, settings |
| **Notifications** | `/api/v1/notifications` | In-app notifications, templates, delivery history, preferences, dispatch API |
| **Audit** | `/api/v1/audit`, `/compliance` | Immutable audit logs, activity feed, compliance policies, retention policies, export |
| **Backup** | `/api/v1/backup` | Storage providers, schedules, manual jobs, file verification, restore history, DR plans |
| **Integration** | `/api/v1/integration` | API keys, webhooks (inbound/outbound), connectors, event registry, request logs |
| **Workflow** | `/api/v1/workflows` | Automation rules with trigger/condition/action definitions, versioning, execution control |
| **Portal** | `/api/v1/portal` | Customer self-service: profile, billing, payments, connections, tickets, notifications |

---

## Security controls

- **Authentication:** HS256 JWT access tokens (in-memory only) + opaque rotating refresh tokens (SHA-256 hashed at rest, HttpOnly/Secure/SameSite=Strict cookies)
- **Authorization:** RBAC middleware with granular permission strings; super-admin wildcard (`*`)
- **Rate limiting:** Per-IP sliding-window rate limiting on authentication endpoints via database-backed middleware
- **Credential encryption:** MikroTik router credentials encrypted with XChaCha20-Poly1305
- **SQL injection prevention:** All database access via PDO prepared statements; no raw string interpolation
- **CORS:** Configurable allowlist; `OPTIONS` preflight short-circuits
- **Security headers:** `SecurityHeadersMiddleware` applied to all responses; stricter in production
- **Password hashing:** Argon2ID via PHP native API
- **Password reset:** Token returned only with explicit `EXPOSE_PASSWORD_RESET_TOKEN=true` (default: false)
- **Trace IDs:** Per-request `X-Trace-Id` for request correlation and audit trail

---

## Production-readiness phases completed

| Phase | PR | Description |
| --- | --- | --- |
| Phase 1 | `refactor(platform): production architecture optimization` | Architecture audit, security review, performance review, database review, technical debt register, refactoring plan, pagination normalization, finance DI fix, password-reset disclosure fix, lazy route adapters |
| Phase 2 | `test(platform): comprehensive automated test suite` | PHPUnit unit tests (28 suites), Vitest + React Testing Library tests, Playwright E2E tests (6 spec files), coverage report |
| Phase 3 | `chore(deployment): production deployment toolkit` | Docker Compose (dev + prod), Dockerfiles (PHP-FPM, Nginx, Supervisor), health/readiness endpoints, GitHub Actions CI/CD workflows, environment templates |
| Phase 4 | `docs(platform): developer documentation` | Architecture guide, API reference + route catalog, database docs, ER diagrams, module docs, folder structure, coding standards, contribution guide, onboarding guide, local dev guide, deployment guide |
| Phase 5 | `docs(operations): operations documentation` | Monitoring guide, backup guide, restore procedures, DR runbook, incident response, upgrade procedures, server maintenance, performance tuning, security checklist, troubleshooting guide |
| Phase 6 | `release(v1.0): production release preparation` | Release notes, CHANGELOG, migration guide, upgrade guide, known issues, acceptance checklist, production checklist, version tag, final audit |

---

## Stack

| Layer | Technology |
| --- | --- |
| Backend | PHP 8.2+ modular monolith, custom router, PDO (MariaDB/MySQL) |
| Frontend | React 18, TypeScript, Vite, Tailwind CSS |
| Server state | TanStack Query |
| Client state | Redux Toolkit (auth session only) |
| Forms | React Hook Form + Zod |
| Auth | HS256 JWT + HttpOnly rotating refresh cookies |
| Authorization | RBAC permissions via `RequirePermissionMiddleware` |
| Database | MariaDB 11.4 / MySQL 8.x compatible |
| Cache | Redis 7.4 |
| Containers | Docker Compose (Nginx, PHP-FPM, Supervisor, MariaDB, Redis) |
| CI/CD | GitHub Actions (CI: lint/test/build, CD: image publish + deployment bundle) |
| Tests | PHPUnit 10, Vitest, React Testing Library, Playwright |

---

## Deployment

See [`docs/deployment/DEPLOYMENT_GUIDE.md`](docs/deployment/DEPLOYMENT_GUIDE.md) for full instructions.

Quick production start:

```bash
cp docker/env/production.env.example .env
# Edit .env — replace ALL secrets and URLs
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d
docker compose -f docker-compose.prod.yml exec backend php database/migrate.php
docker compose -f docker-compose.prod.yml exec backend \
  -e SEED_ADMIN_EMAIL=admin@example.com \
  -e SEED_ADMIN_PASSWORD='secure-password' \
  php database/seed.php
```

Health checks:
- Liveness: `GET /healthz`
- Readiness: `GET /readyz`

---

## Breaking changes from pre-release

This is the first tagged release. There are no breaking changes from a prior version. The following notes apply to the transition from the development branch:

1. **Password reset token disclosure** — Previously, `/api/v1/auth/forgot-password` returned the reset token in the response body. As of Phase 1, the endpoint returns `{requested: true}` only. Set `EXPOSE_PASSWORD_RESET_TOKEN=true` for local development only.
2. **Pagination normalization** — Connections and Monitoring modules now enforce a hard maximum of 100 items per page. Clients sending `per_page > 100` will receive 100. Both JSON:API (`page[number]`/`page[size]`) and legacy (`page`/`per_page`) parameters remain supported.
3. **Finance DI singleton** — The Finance repository contract previously resolved to a cloned instance. It now shares a single singleton, consistent with all other modules.

---

## Contributors

SkyFi was developed as an enterprise ISP management platform with contributions across all functional modules, production-readiness phases, testing, deployment, and documentation.
