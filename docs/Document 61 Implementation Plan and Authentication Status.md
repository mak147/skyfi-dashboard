# Document 61: Implementation Plan and Authentication Status

**Project:** SkyFi Networks ISP Management System
**Document version:** 1.0
**Date:** 2026-07-14
**Status:** Authentication implementation complete; subsequent modules not started

## 1. Purpose

This document records the implementation plan requested after the architecture review and captures decisions made while implementing the Authentication module. It is an additive implementation record; the approved architecture remains the source of truth.

## 2. Architecture understanding

SkyFi is a three-tier system:

- **Presentation tier:** React, TypeScript, Vite, Tailwind CSS SPA. Feature folders own their API hooks, components, types, and routes. TanStack Query owns server cache state; Redux Toolkit owns global UI state; React Hook Form and Zod own forms and client validation.
- **Application tier:** PHP 8.2+ REST API using a modular-monolith boundary. Controllers are thin, services contain use cases, repositories own persistence, and DTOs/contracts keep boundaries testable. Shared concerns live under `src/Shared`.
- **Data tier:** MySQL 8.x/InnoDB with `utf8mb4`, foreign keys, timestamps, soft deletes where required, and versioned migrations. The browser never connects to the database.

The API is versioned under `/api/v1`, uses JSON, standard HTTP status codes, and a JSON:API-inspired `data`/`errors` envelope. The backend is stateless: a short-lived HS256 JWT carries the subject and role claim; an opaque, hashed, single-use refresh token is persisted and rotated. RBAC is seeded from the documented role and permission matrix. Security events and unexpected exceptions are structured JSON logs with trace IDs.

The UI system uses Slate/Indigo/Emerald/Amber/Red semantic colors, Inter/system typography, 4px spacing, rounded form controls, visible focus rings, accessible labels, inline validation errors, responsive layouts, and purposeful motion.

## 3. Documentation-to-development plan

### Phase 0 — Foundation and Authentication (current slice)

Mapped sources: Documents 01, 03 SYS/CP requirements, 04 security and maintainability NFRs, 05–08 architecture and folder structure, 10–14 data/API/auth/RBAC, 17–24 UI/state/routing/forms/validation/errors/logging, 44–45 security/coding, 55–57 testing/QA/monitoring, 59 roadmap, 60 developer guidelines.

Delivered in this slice:

- `backend/` PHP REST API structure with a single public entry point, API v1 auth routes, layered Auth service/repository code, exception handling, trace IDs, JSON logging, migration, and RBAC seed definitions.
- JWT access-token issuance/validation with `iss`, `aud`, `iat`, `nbf`, `exp`, `sub`, and `rol` claims.
- Login, refresh-token rotation, and logout endpoints. Refresh tokens are SHA-256 hashed at rest and delivered only as HttpOnly/SameSite cookies.
- `frontend/` React/Vite authentication feature with Zod + React Hook Form, Redux auth state, in-memory access-token handling, refresh/retry interceptor, protected routing, and the documented design system.
- Unit-test scaffolding for JWT and authentication service behavior.

Not delivered: Dashboard, Customers, Packages, Billing, Payments, Finance, Inventory, Reports, or any external network/payment/notification integration.

### Phase 1 — Dashboard

Mapped sources: Documents 03 REP-001, 16 navigation, 41 Reporting System, 42 Analytics Dashboard, plus 17–20 UI/state/routing. Build only after Authentication is accepted. Establish the protected app layout and role-aware KPI widgets without duplicating server state in Redux.

### Phase 2 — Customers

Mapped sources: Documents 03 CRM-001–006, 28 Customer Management, 29 CRM Architecture, 11 customer/address entities, 14–16 RBAC/navigation. Implement the CRM customer boundary, validation, policies, API resources, and Customer 360 shell.

### Phase 3 — Packages

Mapped sources: Documents 03 BIL-001–002, 26 Billing Architecture, 11 service-plan entity, 14–15 permissions. “Packages” is delivered as the service-plan/product-catalog slice while preserving the documented Billing module boundary.

### Phase 4 — Billing

Mapped sources: Documents 03 BIL-003–006/011–012, 09 dunning specification (later automation), 10–11 invoice entities, 26 Billing Architecture, 21–23 form/validation/error standards. Implement invoice lifecycle, recurring billing, credits, and PDF contracts. Payment processing remains a later slice.

### Phase 5 — Payments

Mapped sources: Documents 03 BIL-007–008, 40 Payment Gateway Architecture, 26 billing payment service, 44 PCI/security. Deliver as a focused integration slice inside the documented Billing boundary, with adapter/tokenization and webhook tests.

### Phase 6 — Finance

Mapped sources: Documents 03 BIL-013, 27 Finance Architecture, 41 financial reports, 43 audit logging. Implement chart of accounts, journal entries, ledger rules, reconciliation, and auditability. This follows the user-requested sequence even though the roadmap places full Finance in Phase 3.

### Phase 7 — Inventory

Mapped sources: Documents 03 INV-001–004, 35 Inventory Management, 10–11 data conventions, 36–37 purchasing/vendor dependencies. Implement items, warehouses, serialized assets, and scoped stock operations; purchasing/vendor work remains a dependency unless explicitly approved as part of this slice.

### Phase 8 — Reports

Mapped sources: Documents 03 REP-002–006, 41 Reporting System, 42 Analytics Dashboard, 53–57 performance/testing/monitoring. Add paginated/filterable/exportable reports over approved module interfaces and read-optimized data paths. Reporting is intentionally last because it depends on stable domain contracts.

## 4. Decisions and discrepancies found during implementation

1. **Repository shape:** The formal Git strategy recommends two repositories, while this checkout is one repository. The implementation keeps the documented `/frontend` and `/backend` roots in this checkout without changing the internal architecture. Splitting repositories remains a deployment/ownership decision.
2. **PHP framework and migration runner:** The documents describe PHP and give Laravel/Phinx as examples but do not select a framework or DI/migration runner. The Auth slice uses framework-neutral PHP 8.2, PDO, PSR-4 autoloading, and a versioned SQL migration so no framework was silently chosen.
3. **User identifier width:** Documents 10–11 describe `users.id` as `INT` in the ERD while the database-wide convention and Authentication document use `BIGINT` for related identifiers. Auth uses `BIGINT UNSIGNED` consistently for `users`, RBAC pivots, and `refresh_tokens.user_id` so MySQL foreign-key types match and future growth is not constrained.
4. **Refresh-token lifecycle fields:** The Authentication document lists expiry but refresh rotation requires replay prevention and logout revocation. The migration therefore adds nullable `used_at` and `revoked_at`, which are implementation details of the documented single-use/rotation behavior.
5. **Authentication response envelope:** The API document requires a JSON:API-inspired envelope, while the Authentication document shows a bare `{accessToken}` example. The implementation follows the API standard: `data.attributes.accessToken` and `data.attributes.user`; the refresh token is never serialized.
6. **Validation error examples:** Documents 12 and 22 show different error shapes. The implementation follows the API Architecture contract (`errors[]` with `status`, `code`, `title`, `detail`, and optional `source.pointer`) and the frontend parses that contract.
7. **Remember-me lifetime:** The Authentication document specifies a 30-day refresh lifetime but not the non-remembered lifetime. Auth uses the documented 30 days when `rememberMe` is enabled and an 8-hour session refresh lifetime otherwise; both are environment-configurable.
8. **Role names:** Documents 14 and 15 use slightly different labels and permission sets in places. The seed follows the more detailed Document 15 matrix and keeps the role names stable for JWT claims.
9. **Requested module names:** Dashboard, Packages, and Payments are delivery slices rather than exact top-level backend modules in the architecture. Payments remains within the documented Billing boundary; Packages maps to Service Plans; Dashboard maps to Analytics/Reporting UI. No code for any of these slices has been added.

## 5. Clarifications required before subsequent modules

- Confirm the selected PHP framework and production migration/queue runner, or approve the framework-neutral foundation used for Authentication.
- Confirm whether the API auth envelope should remain JSON:API (`data.attributes`) or be changed to the bare authentication example; changing it is a versioned contract decision.
- Confirm the final canonical role names/permission matrix where Documents 14 and 15 differ.
- Confirm whether “Packages” is a product name for Service Plans and whether Payments must remain a Billing sub-boundary or be treated as a separately owned delivery slice.
- Confirm whether the requested Dashboard means the staff KPI dashboard only, or also the future customer portal dashboard.
- Confirm the required database migration runner and secret-managed initial-admin seeding process before deployment.

## 6. Definition of done for each next module

A module will be considered complete only when its documented API, migration/entity changes, authorization checks, UI states, responsive/accessibility behavior, unit/integration tests, QA evidence, logging/audit behavior, and documentation updates are present. Work will stop after one module so the next module does not begin implicitly.
