# SkyFi Developer Documentation

**Phase:** 4 — Developer Documentation  
**Audience:** Application engineers, frontend engineers, reviewers, and technical leads  
**Status:** Production-ready reference for the implemented codebase  
**Last reviewed:** 2026-07-15

This directory is the developer-facing documentation for the SkyFi ISP Management System. It describes the **as-built** platform after Phases 1–3 (architecture optimization, automated testing, and the deployment toolkit).

Product vision, functional requirements, and original design drafts remain in `/docs/Document *.md`. Prefer this `docs/developer/` set when onboarding or implementing changes against the current repository.

## Document index

| Document | Purpose |
| --- | --- |
| [01 — Architecture Guide](./01-ARCHITECTURE_GUIDE.md) | Modular monolith, request lifecycle, DI, auth, and frontend architecture |
| [02 — API Reference](./02-API_REFERENCE.md) | REST conventions, envelopes, auth, and module route summary |
| [02b — API Route Catalog](./02-API_ROUTE_CATALOG.md) | Generated listing of all registered HTTP routes |
| [03 — Database Documentation](./03-DATABASE_DOCUMENTATION.md) | Schema conventions, migrations, tables by module |
| [04 — ER Diagrams](./04-ER_DIAGRAMS.md) | Mermaid entity-relationship diagrams for core domains |
| [05 — Module Documentation](./05-MODULE_DOCUMENTATION.md) | Backend and frontend modules, responsibilities, key paths |
| [06 — Folder Structure](./06-FOLDER_STRUCTURE.md) | Repository layout and naming conventions |
| [07 — Coding Standards](./07-CODING_STANDARDS.md) | PHP and TypeScript/React standards enforced in this repo |
| [08 — Contribution Guide](./08-CONTRIBUTION_GUIDE.md) | Branching, PR rules, review checklist, phase discipline |
| [09 — Developer Onboarding Guide](./09-DEVELOPER_ONBOARDING.md) | First-week path for new engineers |
| [10 — Local Development Guide](./10-LOCAL_DEVELOPMENT.md) | Docker and native local setup, seeds, tests, troubleshooting |
| [11 — Deployment Guide](./11-DEPLOYMENT_GUIDE.md) | Pointer to production deployment and CI/CD docs |

## Related documentation

| Location | Contents |
| --- | --- |
| [`docs/deployment/`](../deployment/) | Production Docker Compose, health checks, CI/CD workflows |
| [`docs/production-readiness/`](../production-readiness/) | Phase 1 architecture, security, performance, and debt reports |
| [`TESTING.md`](../../TESTING.md) | Phase 2 automated testing report and coverage summary |
| [`docs/Document 01` … `61`](../) | Original product and architecture design set |

## Stack snapshot

| Layer | Technology |
| --- | --- |
| API | PHP 8.2+ modular monolith, custom router, PDO/MariaDB |
| SPA | React 18, TypeScript, Vite, Tailwind CSS |
| Server state | TanStack Query |
| Client/session state | Redux Toolkit (auth only) |
| Forms | React Hook Form + Zod |
| Auth | HS256 JWT access tokens + HttpOnly refresh cookies |
| Authorization | RBAC permissions (`RequirePermissionMiddleware`) |
| Database | MariaDB 11.x / MySQL 8.x compatible SQL migrations |
| Cache | Redis 7.x |
| Containers | Docker Compose (dev + prod), Nginx, PHP-FPM, Supervisor |
| Tests | PHPUnit, Vitest, React Testing Library, Playwright |

## Quick start

```bash
# From repository root
cp docker/env/development.env.example .env
docker compose up -d --build
docker compose exec backend php database/migrate.php
docker compose exec backend php database/seed.php

# App:  http://localhost:8080
# Vite: http://localhost:5173
# API:  http://localhost:8080/api/v1
```

See [Local Development Guide](./10-LOCAL_DEVELOPMENT.md) for full details.
