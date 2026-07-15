# SkyFi Frontend

React / TypeScript / Vite SPA for the SkyFi ISP Management System (staff admin shell + customer portal).

## Stack

- React 18 + TypeScript + Vite
- Tailwind CSS
- TanStack Query (server state)
- Redux Toolkit (authentication session only)
- React Hook Form + Zod
- Axios API client with silent refresh
- Vitest + React Testing Library; Playwright E2E

## Local setup

Prefer monorepo Docker Compose (see [`docs/developer/10-LOCAL_DEVELOPMENT.md`](../docs/developer/10-LOCAL_DEVELOPMENT.md)).

Native:

```bash
npm install
# VITE_API_BASE_URL defaults to http://localhost:8080/api/v1 or /api/v1 behind Nginx
npm run dev
```

## Scripts

```bash
npm run dev       # Vite dev server
npm run build     # tsc --noEmit && vite build
npm run lint      # ESLint
npx vitest run    # unit tests
npx playwright test  # e2e
```

## Structure

```text
src/
  features/     # Staff feature modules
  portal/       # Customer self-service UI
  components/   # Shared UI
  hooks/        # useAuth, usePermissions
  lib/          # apiClient
  routes/       # Root router + ProtectedRoute
  store/        # Redux auth slice
```

## Authentication behavior

- Access tokens live in memory only (Axios interceptor).
- Refresh token is HttpOnly cookie (`withCredentials: true`).
- On `401` / token expiry, a single-flight refresh retries the original request.
- Refresh failure clears auth and routes to `/login`.

## Documentation

| Topic | Document |
| --- | --- |
| Architecture | [`docs/developer/01-ARCHITECTURE_GUIDE.md`](../docs/developer/01-ARCHITECTURE_GUIDE.md) |
| Modules / features | [`docs/developer/05-MODULE_DOCUMENTATION.md`](../docs/developer/05-MODULE_DOCUMENTATION.md) |
| Coding standards | [`docs/developer/07-CODING_STANDARDS.md`](../docs/developer/07-CODING_STANDARDS.md) |
| Local development | [`docs/developer/10-LOCAL_DEVELOPMENT.md`](../docs/developer/10-LOCAL_DEVELOPMENT.md) |
| Testing report | [`../TESTING.md`](../TESTING.md) |
