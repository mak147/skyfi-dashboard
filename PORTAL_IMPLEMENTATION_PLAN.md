# SkyFi Networks — Customer Self-Service Portal Implementation Plan

## Document Control

| Field | Value |
|-------|-------|
| Module | Customer Self-Service Portal |
| Branch | `arena/019f65fc-skyfi-dashboard` |
| Status | Implementation Plan — Pending Approval |
| Date | 2026-07-15 |

---

## 1. Executive Summary

This plan defines the implementation of the **Customer Self-Service Portal**, a secure, customer-facing React SPA module that consumes existing backend services instead of duplicating business logic. The portal reuses the established JWT authentication, RBAC, billing, payments, support, connections, packages, and notification modules, and adds a thin `Portal` orchestration layer in the backend to enforce customer-scoped data access.

---

## 2. Goals & Constraints

### 2.1 Goals
- Provide customers with a mobile-first, responsive self-service experience.
- Expose only customer-safe endpoints; no staff/admin functionality.
- Reuse existing Authentication, RBAC, Billing, Payments, Support, Connections, Packages, and Notifications modules.
- Follow the SkyFi Design System (Tailwind CSS, cards, skeletons, dark/light mode).
- Keep new database tables to an absolute minimum.

### 2.2 Constraints
- All portal requests are implicitly scoped to the authenticated user's linked `customer_id`.
- No modifications to existing business rules (billing, dunning, provisioning).
- No mobile app, AI features, or new billing logic.
- Only one new schema change is required: linking `users` to `customers`.

---

## 3. Portal Architecture

### 3.1 High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         Customer Browser                                │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │              frontend/src/portal/ (React SPA)                   │   │
│  │  • PortalLayout  • Dashboard  • Billing  • Support  • Profile   │   │
│  └─────────────────────────────────────────────────────────────────┘   │
└───────────────────────────────────┬─────────────────────────────────────┘
                                    │ HTTPS / JSON:API
┌───────────────────────────────────▼─────────────────────────────────────┐
│                         SkyFi PHP REST API                              │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  /api/v1/auth/*          Reuse existing AuthController          │   │
│  │  /api/v1/portal/*        NEW PortalController                   │   │
│  │  /api/v1/me/permissions  Reuse existing RBAC endpoint           │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                              │                                          │
│  ┌───────────────────────────▼────────────────────────────────────┐    │
│  │           backend/src/Portal/ (orchestration layer)              │    │
│  │  PortalController → PortalService → Existing Module Services     │    │
│  └────────────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────────────┘
```

### 3.2 Customer Identity Model

Customer portal users are standard `users` records with:
- A `Customer` role assignment.
- A nullable `customer_id` foreign key pointing to `customers.id`.

This allows full reuse of:
- `users`, `roles`, `permissions`, `role_user`, `permission_role` tables.
- JWT access tokens and refresh-token rotation.
- `RequirePermissionMiddleware` and `JwtAuthMiddleware`.

### 3.3 Backend Module Structure

```text
backend/src/Portal/
├── Contracts/
│   └── PortalServiceContract.php
├── Controllers/
│   ├── PortalAuthController.php      # Forgot/reset/change password (customer-safe)
│   └── PortalController.php          # Dashboard, billing, payments, support, profile
├── DTOs/
│   ├── ChangePasswordData.php
│   ├── ForgotPasswordData.php
│   ├── PortalDashboardData.php
│   ├── ResetPasswordData.php
│   ├── UpdateProfileData.php
│   └── UpdatePreferenceData.php
├── Services/
│   └── PortalService.php
├── Validators/
│   └── PortalValidator.php
└── Routes/
    └── portal.php
```

### 3.4 Backend Layer Responsibilities

| Layer | Responsibility |
|-------|----------------|
| `PortalController` | Parse HTTP requests, enforce RBAC, call `PortalService`, return JSON:API responses. |
| `PortalService` | Resolve `customer_id` from authenticated user, orchestrate calls to existing module services, assemble portal-safe DTOs. |
| Existing Services | Reuse `CustomerService`, `InvoiceService`, `PaymentService`, `ConnectionService`, `TicketService`, `NotificationService`, `PreferenceService`. |
| Repositories | Reuse existing repositories; `PortalService` passes `customer_id` filters to ensure row-level scoping. |

### 3.5 Inter-Module Reuse Map

| Portal Feature | Reused Backend Module | Method / Endpoint |
|----------------|----------------------|-------------------|
| Login / Logout / Refresh | Shared Auth | `POST /api/v1/auth/login`, `/auth/refresh`, `/auth/logout` |
| Dashboard Balance | Billing | `InvoiceRepository::list(customerId)` + aggregate `balance_due` |
| Latest Invoice | Billing | `InvoiceService::list` sorted by `created_at` |
| Recent Payments | Payments | `PaymentService::list` filtered by `customer_id` |
| My Connection | Connections + Packages | `ConnectionService::list(customerId)` + package lookup |
| Invoice List / Detail | Billing | `InvoiceService::list` / `InvoiceService::get` with ownership check |
| Payment List / Receipt | Payments | `PaymentService::list` / `PaymentService::get` with ownership check |
| Tickets | Support | `TicketService::list` / `TicketService::get` / `TicketService::create` / `TicketService::comment` |
| Notifications | Notifications | `NotificationService::list`, `markRead`, `archive` |
| Notification Preferences | Notifications | `PreferenceService::get` / `update` |
| Profile | Customers | `CustomerService::get` / `CustomerService::update` (contact fields only) |

---

## 4. Database Changes

### 4.1 Schema Change (One Migration)

```sql
-- backend/database/migrations/2026_08_10_000000_add_customer_id_to_users.sql
ALTER TABLE users
    ADD COLUMN customer_id BIGINT UNSIGNED NULL AFTER email,
    ADD KEY idx_users_customer_id (customer_id),
    ADD CONSTRAINT fk_users_customer_id
        FOREIGN KEY (customer_id) REFERENCES customers (id)
        ON DELETE SET NULL ON UPDATE CASCADE;
```

### 4.2 No New Tables

Existing tables are sufficient:
- `users`, `customers`, `connections`, `packages`, `invoices`, `payments`, `support_tickets`, `notifications`, `notification_preferences`.

### 4.3 Seeder Update

`backend/database/seeders/AuthSeeder.php` will be updated to:
- Add the `Customer` role.
- Assign portal-scoped permissions to the `Customer` role.

---

## 5. RBAC & Permissions

### 5.1 New Role

| Role | Description |
|------|-------------|
| `Customer` | Self-service portal access; view and manage own data only. |

### 5.2 Permissions Assigned to Customer Role

Reuse the existing scoped permissions that were already designed for this purpose:

- `portal.access`
- `view:customer:own`
- `update:customer:own`
- `view:invoice:own`
- `view:payment:own`
- `view:service:own`
- `view:ticket:own`
- `support.create`
- `manage:ticket:own`
- `notifications.view`
- `notifications.preferences`

### 5.3 Enforcement

- `PortalController` calls `RequirePermissionMiddleware::authorize($userId, 'portal.access')` on every portal route.
- Each service call is additionally scoped by `customer_id` derived from `users.customer_id`.
- A customer can never request another customer's invoice, payment, ticket, or connection because the service layer filters by their own `customer_id`.

---

## 6. API Endpoint List

### 6.1 Authentication (Reuse Existing)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/login` | Login (shared for staff and customers). |
| POST | `/api/v1/auth/refresh` | Refresh access token. |
| POST | `/api/v1/auth/logout` | Logout and revoke refresh token. |

### 6.2 Portal Auth (NEW — customer-safe)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/portal/auth/forgot-password` | Request password reset token. |
| POST | `/api/v1/portal/auth/reset-password` | Reset password using token. |
| POST | `/api/v1/portal/auth/change-password` | Change password while authenticated. |

### 6.3 Portal Data (NEW — customer-safe)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/portal/dashboard` | Dashboard summary (package, balance, latest invoice, recent payments, active tickets, notifications). |
| GET | `/api/v1/portal/connection` | My Connection details (package, speed, PPPoE username, status, router subset). |
| GET | `/api/v1/portal/invoices` | Customer's invoice list (paginated). |
| GET | `/api/v1/portal/invoices/{id}` | Invoice details with items. |
| GET | `/api/v1/portal/invoices/{id}/pdf` | Download invoice PDF placeholder. |
| GET | `/api/v1/portal/balance` | Outstanding balance. |
| GET | `/api/v1/portal/payments` | Customer's payment history (paginated). |
| GET | `/api/v1/portal/payments/{id}` | Payment / receipt details. |
| GET | `/api/v1/portal/payments/{id}/receipt` | Receipt download placeholder. |
| GET | `/api/v1/portal/tickets` | Customer's tickets. |
| POST | `/api/v1/portal/tickets` | Create support ticket. |
| GET | `/api/v1/portal/tickets/{id}` | Ticket detail with comments/timeline. |
| POST | `/api/v1/portal/tickets/{id}/reply` | Add customer reply. |
| POST | `/api/v1/portal/tickets/{id}/close-request` | Request ticket closure (transition to `resolved`). |
| GET | `/api/v1/portal/notifications` | In-app notifications. |
| PATCH | `/api/v1/portal/notifications/{id}/read` | Mark notification read. |
| PATCH | `/api/v1/portal/notifications/read-all` | Mark all notifications read. |
| PATCH | `/api/v1/portal/notifications/{id}/archive` | Archive notification. |
| GET | `/api/v1/portal/profile` | Customer profile. |
| PUT | `/api/v1/portal/profile` | Update personal/contact info. |
| GET | `/api/v1/portal/preferences` | Notification preferences. |
| PUT | `/api/v1/portal/preferences` | Update notification preferences. |

### 6.4 Response Format

All endpoints follow the existing JSON:API-inspired contract:

```json
{
  "data": {
    "type": "portal-dashboard",
    "id": "customer-123",
    "attributes": { ... }
  }
}
```

---

## 7. Frontend Component Structure

### 7.1 Directory Layout

```text
frontend/src/portal/
├── api/
│   ├── portalApi.ts
│   └── usePortal.ts
├── components/
│   ├── CustomerDashboard.tsx
│   ├── InvoiceTable.tsx
│   ├── PaymentTable.tsx
│   ├── TicketList.tsx
│   ├── TicketReplyForm.tsx
│   ├── NotificationPanel.tsx
│   ├── ProfileForm.tsx
│   ├── PasswordForm.tsx
│   ├── ConnectionCard.tsx
│   └── PortalSkeleton.tsx
├── layouts/
│   └── PortalLayout.tsx
├── pages/
│   ├── LoginPage.tsx
│   ├── DashboardPage.tsx
│   ├── MyConnectionPage.tsx
│   ├── BillingPage.tsx
│   ├── InvoiceDetailPage.tsx
│   ├── PaymentsPage.tsx
│   ├── PaymentDetailPage.tsx
│   ├── SupportPage.tsx
│   ├── TicketDetailPage.tsx
│   ├── CreateTicketPage.tsx
│   ├── ProfilePage.tsx
│   ├── NotificationsPage.tsx
│   └── ForgotPasswordPage.tsx
│   └── ResetPasswordPage.tsx
├── routes.tsx
├── types.ts
└── schemas.ts
```

### 7.2 Route Structure

| Route | Page |
|-------|------|
| `/portal/login` | `LoginPage` |
| `/portal/dashboard` | `DashboardPage` |
| `/portal/connection` | `MyConnectionPage` |
| `/portal/billing` | `BillingPage` |
| `/portal/billing/:id` | `InvoiceDetailPage` |
| `/portal/payments` | `PaymentsPage` |
| `/portal/payments/:id` | `PaymentDetailPage` |
| `/portal/support` | `SupportPage` |
| `/portal/support/new` | `CreateTicketPage` |
| `/portal/support/:id` | `TicketDetailPage` |
| `/portal/profile` | `ProfilePage` |
| `/portal/notifications` | `NotificationsPage` |
| `/portal/forgot-password` | `ForgotPasswordPage` |
| `/portal/reset-password` | `ResetPasswordPage` |

### 7.3 UI Patterns

- **Responsive layout**: Mobile-first sidebar/bottom navigation.
- **Cards**: Summary cards for balance, data usage, ticket counts, latest invoice.
- **Tables**: InvoiceTable, PaymentTable, TicketList with pagination.
- **Skeleton loading**: `PortalSkeleton` for dashboard and detail pages.
- **Error handling**: `Alert` component + `apiErrorMessage` helper.
- **Dark/Light mode**: Reuse existing `skyfi-theme` localStorage toggle.

---

## 8. Security Review

| Threat | Mitigation |
|--------|------------|
| Horizontal privilege escalation | Every portal endpoint derives `customer_id` from `users.customer_id`; repository filters enforce row-level scope. |
| Staff accessing portal endpoints | `Customer` role required; staff users without the role are rejected. |
| Token theft | Short-lived JWT access tokens (15 min) + httpOnly refresh-token cookie with `SameSite=Strict`. |
| XSS | Access token stored in memory only; existing CSP and input sanitization patterns followed. |
| CSRF | `SameSite=Strict` refresh cookie; stateless bearer tokens for API calls. |
| Password reset abuse | Reset tokens are cryptographically random, hashed, single-use, and expire after a short TTL. |
| Sensitive data exposure | Portal DTOs exclude internal fields (router credentials, internal notes, cost data, staff-only metadata). |
| Invoice/PDF generation | Placeholder endpoint returns a controlled response; actual document generation remains a future document-service concern. |

---

## 9. Integration Plan

### 9.1 Backend Integration Steps

1. **Schema**: Create migration to add `customer_id` to `users`.
2. **Seeder**: Update `AuthSeeder` to create `Customer` role and permissions.
3. **User Repository**: Add `findCustomerIdByUserId(int $userId): ?int` to `UserRepositoryContract` / `PdoUserRepository`.
4. **AuthService**: Add `forgotPassword`, `resetPassword`, `changePassword` methods (token table already reusable via refresh-token pattern or new `password_resets` records). For this implementation, a lightweight token stored in a new `password_resets` table is acceptable because the requirement explicitly asks for forgot/reset support and no existing table stores reset tokens.
5. **PortalService**: Implement orchestration methods for dashboard, connection, billing, payments, support, profile, notifications.
6. **PortalController**: Implement customer-safe endpoints, RBAC checks, and DTO mapping.
7. **Container**: Register `PortalService`, `PortalController`, `PortalAuthController`.
8. **Routes**: Register `/api/v1/portal/*` routes in `routes/api.php`.

### 9.2 Frontend Integration Steps

1. Create `frontend/src/portal/` feature module.
2. Build `portalApi.ts` using existing `apiClient` (JWT interceptor reused automatically).
3. Build pages and components following existing design system.
4. Add portal routes to `frontend/src/routes/index.tsx` under `/portal/*`.
5. Update `AppLayout` header to distinguish Staff vs. Customer context (optional; portal uses its own `PortalLayout`).
6. Reuse existing `useAuth`, `usePermissions`, `authSlice`, and TanStack Query patterns.

### 9.3 Testing

| Scope | Command / Action |
|-------|------------------|
| Frontend build | `npm run build` |
| Frontend lint | `npm run lint` |
| Backend syntax | `find backend/src/Portal -name "*.php" -exec php -l {} \;` |
| Backend entry | `php -l backend/public/index.php` |

### 9.4 Out of Scope (per requirements)

- Mobile native app.
- AI/ML features.
- New billing logic or automated invoicing.
- Online payment processor integration (placeholder only).
- Real-time bandwidth usage graphs (placeholder only).
- File attachment upload (placeholder UI only).

---

## 10. Implementation Files Summary

### New Backend Files

```text
backend/database/migrations/2026_08_10_000000_add_customer_id_to_users.sql
backend/src/Portal/Contracts/PortalServiceContract.php
backend/src/Portal/Controllers/PortalAuthController.php
backend/src/Portal/Controllers/PortalController.php
backend/src/Portal/Services/PortalService.php
backend/src/Portal/DTOs/ChangePasswordData.php
backend/src/Portal/DTOs/ForgotPasswordData.php
backend/src/Portal/DTOs/PortalDashboardData.php
backend/src/Portal/DTOs/ResetPasswordData.php
backend/src/Portal/DTOs/UpdatePreferenceData.php
backend/src/Portal/DTOs/UpdateProfileData.php
backend/src/Portal/Validators/PortalValidator.php
backend/src/Portal/Routes/portal.php
backend/database/migrations/2026_08_10_000001_create_password_resets_table.sql
```

### Modified Backend Files

```text
backend/database/seeders/AuthSeeder.php
backend/src/Shared/Auth/Contracts/AuthServiceContract.php
backend/src/Shared/Auth/Controllers/AuthController.php
backend/src/Shared/Auth/Repositories/PdoUserRepository.php
backend/src/Shared/Auth/Services/AuthService.php
backend/src/Shared/Auth/Contracts/UserRepositoryContract.php
backend/src/Shared/Providers/Container.php
backend/routes/api.php
```

### New Frontend Files

```text
frontend/src/portal/api/portalApi.ts
frontend/src/portal/api/usePortal.ts
frontend/src/portal/components/CustomerDashboard.tsx
frontend/src/portal/components/InvoiceTable.tsx
frontend/src/portal/components/PaymentTable.tsx
frontend/src/portal/components/TicketList.tsx
frontend/src/portal/components/TicketReplyForm.tsx
frontend/src/portal/components/NotificationPanel.tsx
frontend/src/portal/components/ProfileForm.tsx
frontend/src/portal/components/PasswordForm.tsx
frontend/src/portal/components/ConnectionCard.tsx
frontend/src/portal/components/PortalSkeleton.tsx
frontend/src/portal/layouts/PortalLayout.tsx
frontend/src/portal/pages/LoginPage.tsx
frontend/src/portal/pages/DashboardPage.tsx
frontend/src/portal/pages/MyConnectionPage.tsx
frontend/src/portal/pages/BillingPage.tsx
frontend/src/portal/pages/InvoiceDetailPage.tsx
frontend/src/portal/pages/PaymentsPage.tsx
frontend/src/portal/pages/PaymentDetailPage.tsx
frontend/src/portal/pages/SupportPage.tsx
frontend/src/portal/pages/CreateTicketPage.tsx
frontend/src/portal/pages/TicketDetailPage.tsx
frontend/src/portal/pages/ProfilePage.tsx
frontend/src/portal/pages/NotificationsPage.tsx
frontend/src/portal/pages/ForgotPasswordPage.tsx
frontend/src/portal/pages/ResetPasswordPage.tsx
frontend/src/portal/routes.tsx
frontend/src/portal/types.ts
frontend/src/portal/schemas.ts
```

### Modified Frontend Files

```text
frontend/src/routes/index.tsx
```

---

## 11. Approval Gate

This plan is intentionally scoped to **reuse existing modules** and **minimize schema changes**. Once approved, implementation will proceed in the `arena/019f65fc-skyfi-dashboard` branch and conclude with a pull request titled:

> **feat(portal): implement customer self-service portal**

Please review and approve this plan before coding begins.
