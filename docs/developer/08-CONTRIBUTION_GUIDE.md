# Contribution Guide

How to contribute safely to the SkyFi monorepo.

## 1. Before you start

1. Read [Developer Onboarding](./09-DEVELOPER_ONBOARDING.md) and [Architecture Guide](./01-ARCHITECTURE_GUIDE.md).
2. Confirm whether the work is a **bugfix**, **docs change**, **refactor**, or an **explicitly requested feature**.
3. During production-readiness phases, **do not implement new business features** unless the task explicitly asks for them.
4. Prefer reusing existing modules, DTOs, hooks, and UI primitives.

## 2. Branching model

| Branch | Purpose |
| --- | --- |
| `main` | Integration branch; must stay releasable |
| `arena/*` or feature branches | Active work streams |
| Hotfix branches | Critical production fixes branched from `main` |

Guidelines:

- Branch from the latest `main`.
- Keep branches short-lived.
- Do not force-push shared branches other teams rely on without coordination.
- Arena sessions may be pinned to a specific branch name—honor that pin when present.

## 3. Commit conventions

Use Conventional Commits:

```text
feat(billing): add bulk invoice generation endpoint
fix(auth): rotate refresh token on concurrent refresh
refactor(platform): normalize pagination input
test(customers): cover status transition matrix
docs(platform): developer documentation
chore(deployment): production compose health checks
```

Rules:

- Imperative mood, concise subject (≤72 chars preferred)
- Body explains *why* when non-obvious
- One logical change per commit when practical

## 4. Pull request rules

### 4.1 Production-readiness phase discipline

The roadmap requires:

- **Exactly one phase per PR**
- Do **not** open the next phase until the current PR is merged and a new session starts

Examples of valid single-phase PR titles:

- `refactor(platform): production architecture optimization`
- `test(platform): comprehensive automated test suite`
- `chore(deployment): production deployment toolkit`
- `docs(platform): developer documentation`
- `docs(operations): operations documentation`
- `release(v1.0): production release preparation`

### 4.2 PR description template

```markdown
## Summary
- …

## Motivation
- …

## Files changed
- …

## Validation
- [ ] `docker compose config` / prod compose config
- [ ] Backend: `composer test` or `phpunit`
- [ ] Frontend: `npx vitest run`
- [ ] Frontend: `npm run build` (when UI touched)
- [ ] Playwright (when user flows touched)
- [ ] Manual smoke: login, target module path

## Risk / rollback
- …
```

### 4.3 Review checklist (reviewers)

- [ ] Matches architecture boundaries (controller/service/repository)
- [ ] No accidental feature scope creep
- [ ] Permissions enforced on new/changed endpoints
- [ ] Migrations safe and ordered
- [ ] API envelopes consistent
- [ ] Tests cover new rules
- [ ] Docs updated when contracts/structure change
- [ ] No secrets committed

## 5. Coding workflow

```bash
# 1. Sync
git checkout main
git pull

# 2. Branch
git checkout -b docs/developer-docs   # example

# 3. Implement with local stack
cp docker/env/development.env.example .env
docker compose up -d --build
docker compose exec backend php database/migrate.php

# 4. Validate
cd backend && ./vendor/bin/phpunit
cd ../frontend && npx vitest run && npm run build

# 5. Commit & push
git add -A
git commit -m "docs(platform): developer documentation"
git push -u origin HEAD

# 6. Open PR targeting main
gh pr create --base main --title "docs(platform): developer documentation" --body "..."
```

## 6. Database change policy

1. Add a new timestamped SQL file under `backend/database/migrations/`.
2. Never edit an already-applied migration on shared environments; create a follow-up migration.
3. Include indexes for new filter/join columns when list endpoints need them.
4. Document non-obvious constraints in the migration header comment.
5. Run `--pretend` before applying in shared environments.

## 7. API change policy

| Change type | Allowed in v1? | Notes |
| --- | --- | --- |
| Add optional response field | Yes | Clients must ignore unknowns |
| Add endpoint | Yes | Document in API reference |
| Add optional request field | Yes | Default-safe |
| Rename/remove field | No (without version plan) | Requires deprecation story |
| Change auth cookie semantics | No without security review | High risk |

## 8. Security contributions

- Report suspected vulnerabilities privately to the maintainers; do not open public issues with exploit details.
- Never commit real customer data, production `.env`, or private keys.
- Auth/payment/MikroTik changes require extra review attention.

## 9. Documentation contributions

When you change:

| If you change… | Update… |
| --- | --- |
| Routes | `docs/developer/02-API_REFERENCE.md` |
| Schema | `03-DATABASE_DOCUMENTATION.md`, `04-ER_DIAGRAMS.md` |
| Module boundaries | `05-MODULE_DOCUMENTATION.md`, `01-ARCHITECTURE_GUIDE.md` |
| Local tooling | `10-LOCAL_DEVELOPMENT.md` |
| Deploy tooling | `docs/deployment/*` |

## 10. Code of collaboration

- Be precise and kind in review comments.
- Prefer suggesting patches over vague criticism.
- Separate “blocking” vs “nit” feedback.
- Optimize for long-term maintainability of the modular monolith.
