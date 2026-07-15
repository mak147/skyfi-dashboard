# Phase 1 Database Review

## Current design

MariaDB/MySQL via PDO, 33 ordered SQL migrations, foreign-key-oriented module schemas, soft deletion in applicable aggregates, prepared statements, and service-managed transactions.

## Risks

- Index coverage is not documented against actual repository access patterns.
- Some repositories use `SELECT *`, increasing transfer and coupling to schema order.
- Dynamic table/order fragments rely on local allowlists and need continuing review.
- Finance event listeners contain fixed account identifiers; this is existing behavior and must be migrated to configuration in a separately approved business change.
- Cross-module writes can produce partial state where transaction ownership is unclear.

## Recommendations

- Baseline production-like query plans and slow logs.
- Verify composite indexes in this order: equality predicates, range predicates, then ordering columns.
- Require every list endpoint to enforce `1..100` page size.
- Keep transaction boundaries in services and pass the same PDO-backed repository instances through contracts.
- Add migration rollback/forward validation and schema drift checks in the deployment phase.
- Prefer keyset pagination only for proven large append-only feeds; retain offset contracts elsewhere.
