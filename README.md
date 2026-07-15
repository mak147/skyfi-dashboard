# SkyFi ISP Management System

Enterprise ISP operations platform: customer management, billing, payments, finance, network provisioning (MikroTik / PPPoE / Hotspot), inventory, field service, support, reporting, automation, and a customer self-service portal.

## Stack

| Layer | Technology |
| --- | --- |
| Frontend | React 18, TypeScript, Vite, Tailwind, TanStack Query, Redux Toolkit (auth) |
| Backend | PHP 8.2+ modular monolith, PDO, JWT + HttpOnly refresh cookies |
| Data | MariaDB / MySQL, Redis |
| Runtime | Docker Compose, Nginx, PHP-FPM, Supervisor |

## Quick start (local)

```bash
cp docker/env/development.env.example .env
docker compose up -d --build
docker compose exec backend php database/migrate.php
docker compose exec backend php database/seed.php
```

- Application: http://localhost:8080  
- API: http://localhost:8080/api/v1  
- Health: http://localhost:8080/healthz  

## Documentation

| Audience | Location |
| --- | --- |
| **Developers (start here)** | [`docs/developer/README.md`](docs/developer/README.md) |
| Deployment & CI/CD | [`docs/deployment/`](docs/deployment/) |
| Production-readiness audits | [`docs/production-readiness/`](docs/production-readiness/) |
| Testing report | [`TESTING.md`](TESTING.md) |
| Product / design archive | [`docs/Document *.md`](docs/) |

### Developer guide index

1. [Architecture Guide](docs/developer/01-ARCHITECTURE_GUIDE.md)
2. [API Reference](docs/developer/02-API_REFERENCE.md)
3. [Database Documentation](docs/developer/03-DATABASE_DOCUMENTATION.md)
4. [ER Diagrams](docs/developer/04-ER_DIAGRAMS.md)
5. [Module Documentation](docs/developer/05-MODULE_DOCUMENTATION.md)
6. [Folder Structure](docs/developer/06-FOLDER_STRUCTURE.md)
7. [Coding Standards](docs/developer/07-CODING_STANDARDS.md)
8. [Contribution Guide](docs/developer/08-CONTRIBUTION_GUIDE.md)
9. [Developer Onboarding](docs/developer/09-DEVELOPER_ONBOARDING.md)
10. [Local Development](docs/developer/10-LOCAL_DEVELOPMENT.md)
11. [Deployment Guide (index)](docs/developer/11-DEPLOYMENT_GUIDE.md)

## Repository layout

```text
backend/     PHP API (SkyFi\ modular monolith)
frontend/    React SPA + customer portal
docker/      Nginx, PHP, Supervisor, env templates
docs/        Product, developer, deployment, and readiness docs
```

## License / status

Internal enterprise platform. Functional modules are complete; production-readiness work proceeds in phased PRs (architecture → tests → deployment → developer docs → operations docs → v1.0 release).
