# Coding Standards

Mandatory standards for all SkyFi application code. Automated checks and code review enforce these rules. Historical design: `docs/Document 45 Coding Standards.md`.

## 1. Universal principles

1. **Clarity over cleverness** — prefer readable multi-line code.
2. **DRY** — share via services, hooks, and UI primitives; do not copy domain rules into the SPA.
3. **Single responsibility** — thin controllers, focused services, pure repositories.
4. **Boy Scout rule** — leave touched files cleaner without drive-by redesigns.
5. **Comments explain why** — not what the next line obviously does.
6. **No new business features** during production-readiness work unless explicitly requested.
7. **Backward compatibility** — preserve API paths, envelopes, and auth behavior.

## 2. PHP backend

### 2.1 Baseline

- PHP **8.2+** (containers may run 8.3)
- Every file starts with `declare(strict_types=1);`
- PSR-12 style
- Explicit parameter, return, and property types
- Prefer `final` classes unless extension is intentional
- Constructor property promotion + `readonly` where appropriate
- Interfaces end with `Contract` (or established `Interface` naming already in tree)
- Exceptions end with `Exception`

### 2.2 Architecture rules

| Layer | Allowed | Forbidden |
| --- | --- | --- |
| Controller | Authz, DTO mapping, response shaping | SQL, multi-step domain logic |
| Service | Transactions, status machines, orchestration | HTTP headers/status crafting beyond exceptions |
| Repository | PDO SQL + hydration | Business policy decisions |
| Route file | Wire container + middleware | Domain code |

- Inject **contracts**, not concrete repositories, across module boundaries.
- Use `ApiResponse` for success/error envelopes.
- Throw typed domain exceptions (`NotFoundException`, `ValidationException`, etc.) rather than raw arrays.
- Never log passwords, JWTs, refresh tokens, MikroTik credentials, or full payment card data.

### 2.3 SQL rules

- Parameterized queries only (PDO bound parameters).
- Money columns: `DECIMAL`, never `FLOAT`/`DOUBLE`.
- Schema changes only via migrations.
- Soft-delete aware queries must filter `deleted_at` consistently with existing repositories.

### 2.4 Testing expectations

- New domain rules → PHPUnit unit tests with fakes/mocks where possible.
- Status transition matrices must assert both allowed and blocked transitions.
- Run: `cd backend && composer test` or `./vendor/bin/phpunit`.

### 2.5 Static analysis

- `phpstan.neon` is present; keep new code PHPStan-clean at the project’s configured level when analysis is run in CI.

## 3. TypeScript / React frontend

### 3.1 Baseline

- TypeScript `strict` mode
- Function components only (no new class components)
- Named exports preferred for components and hooks
- Avoid `any`; use `unknown` + narrowing when needed
- Absolute imports via `@/`

### 3.2 File naming

| Kind | Pattern |
| --- | --- |
| Components | `PascalCase.tsx` |
| Hooks | `useCamelCase.ts` |
| Tests | `*.test.ts(x)` next to source or co-located |
| Utilities | `camelCase.ts` |
| Feature dirs | `kebab-case` |

### 3.3 State rules

- **Redux:** authentication session only (`authSlice`).
- **TanStack Query:** all server state.
- **RHF + Zod:** forms and client validation (server remains source of truth).
- Access tokens stay in memory via `apiClient` helpers—never `localStorage`.

### 3.4 UI rules

- Tailwind utility-first styling
- `clsx` for conditional classes
- Reuse `components/ui` primitives before inventing new base controls
- Accessible labels, focus rings, and error messaging on forms

### 3.5 API client rules

- Use shared `apiClient` instance
- Propagate/preserve `X-Trace-Id`
- Let the interceptor handle refresh single-flight; do not invent parallel refresh logic
- Surface API `errors[].detail` to the user when available

### 3.6 Testing expectations

- Component interaction tests with React Testing Library
- Hook tests for shared hooks
- Route guard tests for auth boundaries
- E2E (Playwright) for critical journeys when flows change: auth, RBAC, billing, finance, portal, workflow

```bash
cd frontend
npx vitest run
npx vitest run --coverage
npx playwright test
```

## 4. API contract standards

1. Plural resource nouns, kebab-case multi-word segments.
2. Nest at most one meaningful parent level when required.
3. Prefer JSON:API-inspired `{ data, links, meta }` / `{ errors }`.
4. Support pagination on list endpoints.
5. Authorization failures return `403`; unauthenticated `401`.
6. Do not break existing clients: additive fields are preferred over renames.

## 5. Git and review standards

- Commit messages: conventional style (`feat:`, `fix:`, `refactor:`, `test:`, `docs:`, `chore:`)
- One concern per PR; production-readiness roadmap requires **one phase per PR**
- PR description must include summary, files, and validation commands run
- Do not commit secrets, dumps, or `node_modules`/`vendor`

## 6. Security coding checklist

- [ ] Input validated on server (DTO/validator)
- [ ] Permission checked for mutating and sensitive reads
- [ ] SQL parameterized
- [ ] Sensitive fields excluded from logs and API attributes
- [ ] Cookies: Secure + HttpOnly + SameSite in production
- [ ] CORS origins explicit allow-list
- [ ] File uploads (if any) validated by type/size and stored outside public web root when possible

## 7. Performance checklist

- [ ] List endpoints paginated
- [ ] Avoid N+1 SQL (join or batch load)
- [ ] Frontend lists use query keys + caching sensibly
- [ ] Heavy pages lazy-loaded via `React.lazy`
- [ ] No unbounded `SELECT *` dumps to the browser

## 8. Enforcement

| Gate | Tool |
| --- | --- |
| Backend tests | PHPUnit |
| Frontend lint | ESLint |
| Frontend types/build | `tsc --noEmit` + Vite build |
| Frontend unit tests | Vitest |
| E2E | Playwright |
| Compose validity | `docker compose config` |
| Human review | Architecture, security, domain correctness |

CI workflow template: `docs/deployment/github-actions/ci.yml`.
