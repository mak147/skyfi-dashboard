# Folder Structure

This document reflects the **current monorepo layout** of SkyFi (frontend + backend + Docker + docs).

## 1. Repository root

```text
skyfi-dashboard/
├── backend/                 # PHP REST API
├── frontend/                # React SPA
├── docker/                  # Nginx, PHP, Supervisor, env templates, scripts
├── docs/                    # Product docs + developer/ops/deployment docs
├── docker-compose.yml       # Local development stack
├── docker-compose.prod.yml  # Production stack
├── .env.example             # Root env template (Compose + app secrets)
├── .dockerignore
├── .gitignore
├── TESTING.md               # Phase 2 testing report
└── *IMPLEMENTATION*.md      # Historical feature implementation notes
```

## 2. Backend (`backend/`)

```text
backend/
├── public/
│   ├── index.php            # Application front controller
│   ├── healthz.php
│   └── readyz.php
├── routes/
│   ├── api.php              # Registers all module routes
│   ├── auth.php
│   ├── rbac.php
│   └── dashboard.php
├── config/
│   ├── app.php
│   ├── cors.php
│   ├── database.php
│   ├── mikrotik.php
│   └── services.php
├── database/
│   ├── migrations/          # Ordered *.sql schema files
│   ├── seeders/
│   ├── Migrator.php
│   ├── migrate.php
│   └── seed.php
├── src/                     # PSR-4 SkyFi\
│   ├── Shared/              # Kernel
│   ├── Rbac/
│   ├── Customers/
│   ├── ...                  # One folder per business module
│   └── Workflow/
├── storage/                 # Logs, cache, runtime files (gitignored content)
├── tests/
│   ├── Unit/
│   ├── Integration/
│   └── Feature/
├── composer.json
├── phpunit.xml
├── phpstan.neon
├── Dockerfile
└── README.md
```

### Module internal layout

```text
src/{Module}/
├── Contracts/
├── Controllers/
├── Data/ or DTOs/
├── Models/ or DomainModels/
├── Repositories/
├── Routes/
├── Services/
└── Validators/              # when present
```

### Shared kernel highlights

```text
src/Shared/
├── Auth/                    # Controllers, services, repositories for JWT/refresh
├── Config/
├── Events/
├── Exceptions/
├── Http/                    # Request, Response, Router, ApiResponse, Middleware, Pagination
├── Logging/
└── Providers/               # Container composition root
```

## 3. Frontend (`frontend/`)

```text
frontend/
├── index.html
├── package.json
├── vite.config.ts
├── vitest.config.ts
├── playwright.config.ts
├── tailwind.config.js
├── postcss.config.js
├── tsconfig.json
├── Dockerfile
├── e2e/                     # Playwright specs
├── public/
└── src/
    ├── main.tsx
    ├── vite-env.d.ts
    ├── assets/styles/
    ├── components/
    │   ├── ui/              # Primitives (button, input, ...)
    │   └── common/          # App-level shared components
    ├── config/
    ├── features/            # Feature modules (staff app)
    │   ├── authentication/
    │   ├── billing/
    │   ├── customers/
    │   └── ...
    ├── hooks/               # Shared hooks (useAuth, usePermissions)
    ├── layouts/
    ├── lib/                 # apiClient and utilities
    ├── portal/              # Customer portal app surface
    ├── providers/
    ├── routes/              # Root router + ProtectedRoute
    ├── store/               # Redux store (auth)
    └── test/                # Test helpers
```

### Feature folder convention

```text
features/{name}/
├── api/
├── components/
├── pages/
├── routes/                  # optional nested routes
└── types.ts                 # optional
```

## 4. Docker toolkit (`docker/`)

```text
docker/
├── env/
│   ├── development.env.example
│   └── production.env.example
├── nginx/                   # dev and prod conf
├── php/                     # FPM / PHP config
├── supervisor/              # worker programs
└── scripts/                 # helper operational scripts
```

CI/CD workflow **templates** live under:

```text
docs/deployment/github-actions/
├── ci.yml
└── cd.yml
```

Copy into `.github/workflows/` when the repository environment allows workflow writes.

## 5. Documentation (`docs/`)

```text
docs/
├── Document 01 … 61 …       # Original product/architecture design set
├── deployment/              # Phase 3 deployment + CI/CD guides
├── production-readiness/    # Phase 1 audit reports
└── developer/               # Phase 4 developer documentation (this set)
```

## 6. Path aliases

| Alias | Resolves to |
| --- | --- |
| `@/` (frontend) | `frontend/src/` |
| `SkyFi\` (backend PSR-4) | `backend/src/` |
| `SkyFi\Tests\` | `backend/tests/` |

## 7. Naming conventions (filesystem)

| Kind | Convention |
| --- | --- |
| PHP classes | `PascalCase.php` |
| PHP namespaces | `SkyFi\{Module}\{Layer}` |
| React components | `PascalCase.tsx` |
| Hooks | `useCamelCase.ts` |
| Feature folders | `kebab-case` (`field-service`) |
| Route path params | `{id}`, `{routerId}`, etc. |
| SQL migrations | `{timestamp}_{description}.sql` |

## 8. What not to commit

- `.env` and real secrets
- `vendor/`, `node_modules/`, build outputs (`dist/`)
- Local DB dumps with production data
- IDE user-specific files unless project-shared (e.g. recommended extensions)

See root `.gitignore` and `.dockerignore` for the authoritative lists.
