# Developer Onboarding Guide

Welcome to SkyFi. This guide gets a new engineer productive on the **as-built** platform.

## Day 0 — Access and prerequisites

### Accounts

- Git repository access
- Container registry access (if deploying images)
- Shared password manager entry for non-prod credentials (never reuse production secrets)

### Workstation tools

| Tool | Version guidance |
| --- | --- |
| Git | 2.40+ |
| Docker Engine + Compose plugin | Engine 25+, Compose 2.24+ |
| Node.js | 20 or 22 LTS (matches CI template) |
| PHP | 8.2+ (only if running API outside Docker) |
| Composer | 2.x (native backend work) |
| Optional | `gh` CLI, TablePlus/DBeaver, VS Code/Cursor |

### Recommended editor extensions

- ESLint, Tailwind CSS IntelliSense
- PHP Intelephense
- Docker, GitHub Pull Requests
- Mermaid preview (for ER diagrams)

## Day 1 — Run the stack

1. Clone the repository and enter the root.
2. Follow [Local Development Guide](./10-LOCAL_DEVELOPMENT.md) to start Compose, migrate, and seed.
3. Verify:
   - `http://localhost:8080` loads the SPA shell
   - `http://localhost:8080/api/v1` returns API responses (auth errors without token are expected)
   - `http://localhost:8080/healthz` and `/readyz` succeed
4. Log in with the seeded admin credentials from your local env.

**Success criteria:** you can authenticate, open the dashboard, and load at least Customers and Invoices lists.

## Day 2 — Architecture literacy

Read in this order:

1. [Architecture Guide](./01-ARCHITECTURE_GUIDE.md)
2. [Folder Structure](./06-FOLDER_STRUCTURE.md)
3. [Module Documentation](./05-MODULE_DOCUMENTATION.md) (skim the map, deep-read your assigned domain)
4. Phase 1 audit: `docs/production-readiness/ARCHITECTURE_AUDIT.md`
5. [API Reference](./02-API_REFERENCE.md) sections for Auth, Customers, Billing

Trace one vertical slice end-to-end:

```text
Login form
  → apiClient POST /auth/login
  → AuthController → AuthService → repositories
  → JWT + refresh cookie
  → Redux sessionStarted
  → ProtectedRoute → Dashboard
  → GET /dashboard with Bearer token
```

**Success criteria:** you can explain where to put a new endpoint and where **not** to put SQL.

## Day 3 — Make a safe change

Pick a non-production-risk task (docs typo, test hardening, or assigned bug).

Checklist:

- [ ] Branch from latest `main`
- [ ] Follow [Coding Standards](./07-CODING_STANDARDS.md)
- [ ] Add/adjust tests
- [ ] Run unit tests locally
- [ ] Open PR using [Contribution Guide](./08-CONTRIBUTION_GUIDE.md) template

## Domain orientation (first two weeks)

| If you work on… | Read first | Code anchors |
| --- | --- | --- |
| Auth/RBAC | Architecture §3.5, API Auth/RBAC | `Shared/Auth`, `Rbac`, `authSlice`, `apiClient` |
| CRM | Customers module docs | `Customers/*`, `features/customers` |
| Billing/Payments | Billing API + DB docs | `Billing/*`, `Payments/*` |
| Network | MikroTik/Hotspot/PPPoE modules | `Mikrotik`, `Hotspot`, `Pppoe` |
| Portal | Portal module + portal frontend | `Portal/*`, `frontend/src/portal` |
| Platform/DevOps | Deployment guide + Compose files | `docker/`, `docker-compose*.yml` |

## Mental models to internalize

1. **SPA is a client, not the source of truth.** Business rules live in PHP services.
2. **JWT is not a session store.** Refresh cookies + rotation matter.
3. **Modules own their tables.** Cross-module writes go through services/contracts.
4. **TanStack Query ≠ Redux.** Do not duplicate server lists in Redux.
5. **Migrations are immutable history.** Fix forward with new files.

## Common first-week pitfalls

| Pitfall | Correct approach |
| --- | --- |
| Storing access token in `localStorage` | Keep memory-only token via `apiClient` |
| Querying another module’s tables from a foreign repository | Call that module’s service/contract |
| Returning ad-hoc JSON shapes | Use `ApiResponse` resource/collection helpers |
| Editing old migrations already applied elsewhere | Add a new migration |
| Implementing “just a small feature” during a docs/test phase | Stay in phase scope |

## Who to ask

| Topic | Owner role |
| --- | --- |
| Architecture boundaries | Tech lead / principal engineer |
| Production deploys | DevOps / platform |
| Billing correctness | Domain owner + finance stakeholder |
| Security incidents | Security contact (see ops docs in Phase 5) |

## Onboarding completion checklist

- [ ] Local stack runs with seed data
- [ ] Can explain request lifecycle from Nginx to PDO
- [ ] Can find routes for a given UI page
- [ ] Ran backend PHPUnit and frontend Vitest once
- [ ] Submitted first PR following contribution rules
- [ ] Knows where Phase 1–3 artifacts live (`docs/production-readiness`, `TESTING.md`, `docs/deployment`)
