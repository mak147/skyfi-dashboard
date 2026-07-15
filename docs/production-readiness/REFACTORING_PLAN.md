# Production Refactoring Plan

## Completed in Phase 1

- Audited backend, frontend, migrations, tests, completed modules, and all `/docs` material.
- Added one backward-compatible pagination boundary supporting JSON:API and legacy query forms with a 100-row cap.
- Adopted it in Connections and Monitoring DTOs.
- Corrected Finance contract registration so one repository instance is injected.
- Corrected lazy route adapters so named route exports produce valid, split production bundles.
- Closed unconditional password-reset token disclosure with a default-off development switch.
- Recorded architecture, security, performance, database, and debt decisions.

## Safe follow-up sequence

These items are recommendations, not authorization to begin another roadmap phase.

1. Add characterization tests around the composition root and high-risk services.
2. Extract one module registration provider at a time behind the unchanged `Container::get()` API.
3. Introduce shared filter primitives and query-key factories during normal module maintenance.
4. Break large services into private use-case collaborators while preserving public contracts.
5. Optimize only measured SQL and add indexes through reversible migrations.
6. Add route-level lazy loading and shared async-state components without changing workflows.

## Definition of done for refactors

No route or payload break; no removed feature; static analysis clean; backend/frontend builds pass; tests pass; migration/query evidence attached where relevant; rollback is straightforward.
