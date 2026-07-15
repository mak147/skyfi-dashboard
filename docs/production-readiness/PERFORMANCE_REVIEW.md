# Phase 1 Performance Review

## Assessment

The application uses bounded list queries in most modules and TanStack Query caching in the SPA. Primary risk comes from high-cardinality dashboards, repeated relation loading, unbounded legacy filters, `SELECT *`, and eager construction of all services per request.

## Changes in this phase

- Centralized pagination normalization with a hard maximum of 100 rows.
- Applied bounded pagination to Monitoring and Connections while preserving old and JSON:API parameters.
- Removed a duplicate Finance repository instance.

## Prioritized work

1. Capture p50/p95/p99 route latency and slow-query logs before changing SQL.
2. Add/verify composite indexes for filter + ordering patterns, soft-delete predicates, foreign keys, token expiry, and job status/next-run fields.
3. Replace `SELECT *` on hot paths with explicit projections after query-plan measurement.
4. Batch relation hydration and dashboard aggregates; avoid per-row queries.
5. Split the composition root into lazy module providers to reduce request allocation.
6. Add frontend route-level code splitting; keep query keys stable and tune stale time by volatility.

Performance changes require `EXPLAIN`, representative cardinality, and before/after evidence; query rewrites based only on style are prohibited.
