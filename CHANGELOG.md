# Changelog

All notable changes to the SkyFi ISP Management System are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0] — 2026-07-15

### Added

#### Authentication & Authorization
- JWT access token authentication (HS256, in-memory only on the SPA)
- Rotating opaque refresh tokens with SHA-256 hashing at rest and HttpOnly/Secure/SameSite=Strict cookies
- Login, refresh, logout, forgot-password, reset-password, change-password endpoints
- Per-IP sliding-window rate limiting on authentication endpoints
- RBAC system with 10 predefined roles and 80+ granular permission strings
- Super-admin wildcard permission (`*`) support
- `GET /api/v1/me/permissions` endpoint for effective permission queries

#### Customer Management
- Customer CRM with full lifecycle: lead → prospect → active → suspended → disconnected → archived
- Enforced status transition validation (invalid jumps rejected with `ValidationException`)
- Customer list, create, detail, update, delete, status change endpoints
- Customer export capability

#### Package Management
- Service catalog with categories, pricing profiles, bandwidth profiles, network profiles, billing settings, technical profiles, and customer rules
- Package create, duplicate, update, soft-delete, export operations
- Package status management and bulk catalog actions

#### Connections
- Customer service connection management with activate, suspend, disconnect, transfer operations
- Infrastructure foreign key integration (POP sites, towers, sectors)

#### Billing
- Invoice CRUD with create, generate, bulk-generate workflows
- Invoice status machine: draft → pending → issued → paid → void (with enforced transition rules)
- Billing schedules and late fee rules
- Invoice activity trail (immutable audit log of invoice changes)
- Invoice export

#### Payments
- Payment receipt, allocation to invoices, reversal, and refund
- Customer credit ledger
- Payment methods and payment accounts (cash/bank channels)
- Receipt and PDF export
- Payment activity trail and financial event hooks

#### Finance
- Chart of accounts with financial account management
- Journal entries with double-entry integrity enforcement
- General ledger queries
- Expense and revenue tracking
- Finance dashboard aggregates (revenue, expenses, operating margin)

#### Network Provisioning — MikroTik
- Router inventory, groups, and tags
- TLS-based API connectivity test
- Router discovery and health snapshot capture
- XChaCha20-Poly1305 encryption for stored router credentials
- Router validation and credential cipher services

#### Network Provisioning — PPPoE
- PPPoE account lifecycle management
- Session monitoring (active sessions and history)
- Synchronization audit, discrepancy detection, repair, and import

#### Network Provisioning — Hotspot
- Hotspot profiles, users, and voucher management
- Batch voucher generation and print formatting
- Active session management with disconnect and force-logout
- Router sync: detect missing, import, repair, per-router and per-user sync
- User status management (suspend, resume, enable, disable, reset password)

#### Infrastructure
- Physical network model: POP Sites → Towers → Sectors → Network Devices
- CRUD for all infrastructure entities
- Infrastructure FK integration with connections

#### Monitoring
- Device status history and interface snapshot capture
- Alert management with dashboard
- Bounded pagination for high-cardinality data

#### Support
- Ticket management with teams, categories, and SLA tracking
- Ticket lifecycle: open → in_progress → resolved → closed (with reopen capability)
- Assignment, escalation, merge, split, and cancel operations
- Comment panel with internal notes and customer replies
- SLA dashboard with performance metrics
- Ticket timeline and workflow actions

#### Inventory
- Products, warehouses, stock levels, and assets
- Stock movements, transfers, and adjustments
- Opening balances, damage/scrap, and reversals
- Finance posting integration

#### Purchasing
- Procurement flow: Purchase Requests → Purchase Orders → Goods Receipts → Supplier Invoices
- Approval/rejection workflow for requests and orders
- Partial receipt and return-to-supplier handling
- Procurement dashboard and statistics

#### Vendor Management
- Supplier master data with contacts, contracts, and quotations
- Supplier rating system and performance cards
- Price comparison tables
- Category manager and status management

#### Field Service
- Technician management with availability and scheduling
- Installation request workflow
- Work order lifecycle with materials and visit logs
- Field service dashboard with unscheduled work view
- Operation validation

#### Reports
- Report catalog with generation and saved reports
- Report builder with filter panel, KP1 cards, and chart components (bar, line, pie)
- Scheduled reports and export center
- Export to spreadsheet and PDF (via PhpSpreadsheet and DomPDF)

#### System Administration
- Company profile and multi-branch management
- Department management
- Branding (logo upload, theme selector)
- Localization settings
- Notification preferences
- System settings with maintenance mode flags

#### Notifications
- In-app notification center with inbox and unread counts
- Notification templates across channels
- Delivery history and preferences
- Manual dispatch API
- Event publishers and event subscribers for cross-module notification triggers

#### Audit & Compliance
- Immutable-style audit log queries with resource history
- Activity feed per user and per resource
- Compliance policies and retention policies
- Audit export jobs with download

#### Backup & Disaster Recovery
- Storage provider management
- Backup schedules and manual job execution
- File verification history
- Restore execution and restore history
- DR plan management

#### Integration
- Client application and API key management with scope control
- Outbound webhooks with delivery history and retry
- Inbound webhook endpoint for external integrations
- Connectors and request log management
- Event registry and event subscribers

#### Workflow Automation
- Automation rules with trigger/condition/action definitions
- Workflow versioning and enable/disable control
- Manual run and test execution
- Execution control: retry, cancel, pause, resume
- Scheduler tick for time-based triggers
- Trigger and action catalogs

#### Customer Portal
- Customer self-service dashboard
- Profile management (view and update own data)
- Connection details (MAC, IP, subscription info)
- Invoice listing with "Pay Now" action
- Payment history and details
- Ticket creation, listing, and detail with reply form
- Notification panel
- Password management (forgot/reset/change)
- Portal-specific layout and routing

#### Frontend Infrastructure
- React 18 SPA with TypeScript, Vite, and Tailwind CSS
- Feature-based module organization under `frontend/src/features/`
- TanStack Query for server state management
- Redux Toolkit for auth session state only
- React Hook Form + Zod for form validation
- Axios API client with automatic token refresh and trace ID injection
- Lazy-loaded feature routes for production code splitting
- Protected route guard with permission-based access control
- App layout with sidebar navigation
- Shared UI component library (Button, etc.)

#### Deployment Toolkit
- Docker Compose for development (hot-reload, dev server, exposed ports)
- Docker Compose for production (optimized multi-stage builds, health checks, required env vars)
- Backend Dockerfile with `development`, `php-fpm`, and `supervisor` targets
- Frontend Dockerfile with `development` and `static` targets
- Nginx configuration for development and production
- PHP-FPM configuration (`www.conf`, `php.ini`, `php.dev.ini`)
- Supervisor configuration with skyfi-worker script
- Health endpoint (`/healthz`) for liveness checks
- Readiness endpoint (`/readyz`) for database + storage checks
- Environment templates for development and production
- CI workflow (GitHub Actions): backend lint/test, frontend lint/test/build, Docker build validation
- CD workflow (GitHub Actions): image build, GHCR publish, deployment bundle artifact

#### Developer Documentation
- Architecture guide (modular monolith, request lifecycle, DI, auth, frontend architecture)
- API reference with conventions, envelopes, and auth flows
- API route catalog (537 registered routes)
- Database documentation (165 tables, naming conventions, migration runner)
- ER diagrams (Mermaid) for all core domains
- Module documentation (26 modules with deep dives)
- Folder structure reference
- Coding standards (PHP and TypeScript/React)
- Contribution guide (branching, PR rules, review checklist, phase discipline)
- Developer onboarding guide (first-week path)
- Local development guide (Docker and native setup)
- Deployment guide (pointer to production deployment docs)

#### Operations Documentation
- Monitoring guide (metrics, alerts, log aggregation)
- Backup guide (automated schedules, off-site replication)
- Restore procedures (SQL/file restoration, checksum validation, PITR)
- Disaster recovery runbook (regional failover, Pilot Light, DNS switchover)
- Incident response playbook (severity levels, triage, RCA)
- Upgrade procedures (zero-downtime migrations, blue-green deployment)
- Server maintenance guide (patching, OS upgrades, disk compaction, log rotation)
- Performance tuning guide (PHP-FPM sizing, InnoDB buffer pool, Redis eviction, indexing)
- Security checklist (secrets rotation, TLS hardening, SSH hardening, rate limiting, dependency auditing)
- Troubleshooting guide (502 Bad Gateway, locks, Redis memory, network diagnostics, trace patterns)

#### Testing
- 28 PHPUnit unit test files covering all backend modules
- Frontend unit/integration tests: auth hook, permissions hook, auth slice, protected route, button component
- 6 Playwright E2E test scenarios: authentication, RBAC, billing, finance, customer portal, workflow
- Vitest coverage reporting (V8 provider)

### Changed

- **Pagination normalization:** Connections and Monitoring list endpoints now enforce a hard maximum of 100 items per page. Both JSON:API (`page[number]`/`page[size]`) and legacy (`page`/`per_page`) parameters are supported.
- **Finance DI singleton:** The Finance repository contract now resolves to a single shared instance, consistent with all other module patterns.
- **Password reset token disclosure:** `/api/v1/auth/forgot-password` no longer returns reset tokens in the response body. Returns `{requested: true}` only. Opt-in via `EXPOSE_PASSWORD_RESET_TOKEN=true` for local development.

### Fixed

- Password reset credentials were returned by the API regardless of environment — now explicitly gated behind a default-off development flag.
- Finance repository contract resolved to a cloned instance instead of a singleton — now shares one instance.
- Lazy route adapters did not produce valid split production bundles — corrected named exports.

### Security

- All database queries use PDO prepared statements (no SQL injection vectors).
- MikroTik router credentials encrypted at rest with XChaCha20-Poly1305.
- JWT access tokens stored in memory only (never `localStorage`).
- Refresh tokens hashed (SHA-256) at rest, served via HttpOnly/Secure/SameSite=Strict cookies.
- Rate limiting on authentication endpoints.
- CORS allowlist enforced; `OPTIONS` preflight returns 204.
- Security headers applied to all responses via `SecurityHeadersMiddleware`.
- Argon2ID password hashing.
- Trace ID correlation on all requests and error responses.

---

## [0.1.0] — 2026-07-14

### Added

- Initial development release with all functional modules implemented.
- Modular monolith architecture with 26 backend modules and 25 frontend features.
- MariaDB schema with 33 migrations.
- Customer portal with dedicated routing and layout.
- Basic Docker Compose development environment.
