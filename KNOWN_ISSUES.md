# SkyFi ISP Management System — Known Issues v1.0.0

**Last updated:** 2026-07-15  
**Scope:** Known limitations, issues, and deferred items as of the v1.0.0 release  

---

## 1. Architecture & code

| ID | Severity | Area | Issue | Workaround / Notes |
| --- | --- | --- | --- | --- |
| KI-001 | Medium | DI Container | The composition root (`Container`) is a large (~1,300+ line) eager constructor. All services are instantiated on every request, increasing per-request allocation. | Planned incremental provider extraction. No functional impact; performance measured before acting. |
| KI-002 | Low | Naming variance | DTO directories use mixed naming: some modules use `Data/`, others use `DTOs/`, and domain models use either `Models/` or `DomainModels/`. | No functional impact. Will converge opportunistically when modules are touched; no namespace-breaking moves planned. |
| KI-003 | Low | Compact source | Some module source files contain compact/minified formatting (single-line conditionals, reduced whitespace). | Aesthetic only. Will format alongside tests; no behavior redesign. |
| KI-004 | Medium | Service size | Several services and repositories exceed 500–850 lines, mixing multiple use cases. | Planned split into use-case collaborators with characterization tests. No current functional impact. |

## 2. Security

| ID | Severity | Area | Issue | Workaround / Notes |
| --- | --- | --- | --- | --- |
| KI-005 | High | Authorization consistency | Not all module endpoints have uniform permission middleware placement. Some authorization checks exist only at the controller level rather than as route middleware. | Documented permission matrix exists. API tests will close this gap in future releases. Deny-by-default endpoint matrix to be enforced. |
| KI-006 | Medium | Rate limiting scope | Database rate limiting protects selected authentication endpoints only. Refresh-token and sensitive export endpoints are not yet rate-limited. | Extend rate-limiting policy to additional endpoints after load testing. |
| KI-007 | Medium | CORS/cookies | Production deployment requires HTTPS, `REFRESH_COOKIE_SECURE=true`, exact origin allowlists, and trusted proxy configuration. | Ensure TLS termination is configured before the Nginx container. Default production Compose sets `REFRESH_COOKIE_SECURE=true`. |
| KI-008 | Medium | File/export surfaces | Import and export endpoints need additional enforcement of MIME type validation, size limits, storage isolation, filename sanitization, and spreadsheet formula escaping. | Current implementation has basic safeguards. Full hardening planned. |
| KI-009 | Medium | Broad exception catches | Integration module fallbacks catch broad exception types, potentially masking programming defects. | Structured contextual logging planned to distinguish expected remote failures from programming defects. |

## 3. Performance

| ID | Severity | Area | Issue | Workaround / Notes |
| --- | --- | --- | --- | --- |
| KI-010 | High | Query baseline | No production-like query performance baseline exists. Slow-query logs and `EXPLAIN` analysis have not been captured against representative data volumes. | Must baseline p50/p95/p99 latency and slow queries before optimizing SQL. See `docs/production-readiness/PERFORMANCE_REVIEW.md`. |
| KI-011 | Medium | SELECT * usage | Some repositories use `SELECT *`, increasing data transfer and coupling to column order. | Replace with explicit column projections on hot paths after query-plan measurement. |
| KI-012 | Medium | Dashboard aggregation | High-cardinality dashboard queries may cause slow response times at scale without pre-computed aggregates or materialized views. | Batch relation hydration and dashboard aggregates after measurement. |
| KI-013 | Low | Frontend code splitting | Route-level code splitting is implemented, but further optimization (component-level lazy loading, shared async-state components) can reduce initial bundle size. | Incremental improvement; no current blocking issue. |

## 4. Database

| ID | Severity | Area | Issue | Workaround / Notes |
| --- | --- | --- | --- | --- |
| KI-014 | Medium | Index coverage | Index coverage is documented but not verified against actual repository access patterns under load. | Verify composite indexes for equality predicates, range predicates, and ordering columns. See `docs/production-readiness/DATABASE_REVIEW.md`. |
| KI-015 | Medium | Finance hardcoded accounts | Finance event listeners contain fixed account identifiers. These should be migrated to configuration. | Existing behavior; requires a separately approved business change to refactor. |
| KI-016 | Low | Dynamic SQL fragments | Dynamic table names and ORDER BY clauses rely on local allowlists. Continuing review is needed. | Current implementation uses parameterized allowlists. No known injection risk. |

## 5. Testing

| ID | Severity | Area | Issue | Workaround / Notes |
| --- | --- | --- | --- | --- |
| KI-017 | Medium | Frontend coverage | Frontend automated coverage is limited to core shared code (hooks, store, routes, Button component). Feature modules lack dedicated component/hook tests. | Core infrastructure is tested at 100% coverage. Feature tests to be expanded incrementally. |
| KI-018 | Low | E2E scope | E2E tests cover 6 critical business flows (auth, RBAC, billing, finance, portal, workflow). Remaining 20+ modules lack dedicated E2E coverage. | Critical paths are covered. Additional E2E tests to be added per module as needed. |
| KI-019 | Low | Backend integration tests | Backend test suites are primarily unit tests with mock dependencies. Full integration tests against a real database are not yet implemented. | Integration tests to be added with a test database container in CI. |

## 6. Operations

| ID | Severity | Area | Issue | Workaround / Notes |
| --- | --- | --- | --- | --- |
| KI-020 | Low | Observability | No centralized observability stack (Datadog/CloudWatch/etc.) is pre-configured. Trace IDs and structured logging exist but require external aggregation. | See `docs/operations/01-MONITORING_GUIDE.md` for integration instructions. |
| KI-021 | Low | Automated backup | Backup scheduling is available via the application UI/API, but external cron-based database dumps require manual setup. | See `docs/operations/02-BACKUP_GUIDE.md` for external scheduler configuration. |

---

## Reporting new issues

If you discover an issue not listed here, please open a GitHub Issue with:

1. Steps to reproduce
2. Expected vs actual behavior
3. Environment details (Docker version, browser, API version)
4. Relevant log excerpts (with sensitive data redacted)
