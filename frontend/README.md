# SkyFi Frontend — Authentication Slice

React/TypeScript/Vite SPA for the SkyFi Networks ISP Management System. This implementation contains only the authentication experience and the minimal protected shell used to verify it. Dashboard, Customers, Packages, Billing, Payments, Finance, Inventory, and Reports are intentionally not implemented.

## Local setup

```bash
cp .env.example .env
npm install
npm run dev
```

The API is expected at `VITE_API_BASE_URL` (default: `http://localhost:8080/api/v1`).

## Authentication behavior

- React Hook Form + Zod validate login input with `mode: onTouched`.
- Redux Toolkit owns the global auth state.
- Access tokens live in memory only and are attached by the Axios request interceptor.
- The refresh token is an HttpOnly cookie and is never readable by JavaScript.
- A `401` with `token_expired` queues one refresh request, rotates the cookie, updates Redux, and retries the original request.
- Refresh failure clears auth state and returns the user to `/login`.
- The form and protected shell use the documented Indigo/Slate design system and accessible focus/error patterns.
