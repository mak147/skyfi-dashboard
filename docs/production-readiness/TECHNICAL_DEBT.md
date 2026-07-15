# Phase 1 Technical Debt Register

| ID | Priority | Debt | Exit criterion |
|---|---|---|---|
| TD-001 | P1 | Monolithic eager DI container | Module providers, lazy factories, contract identity tests |
| TD-002 | P1 | Large Support/Notification/Workflow/Hotspot classes | Use-case/query classes under existing facades |
| TD-003 | P1 | Uneven endpoint authorization placement | Documented permission matrix and API tests |
| TD-004 | P1 | Limited frontend automated coverage | Address in Phase 2 only |
| TD-005 | P1 | Unknown index/query baseline | Explain plans and slow-query baseline |
| TD-006 | P2 | Repeated pagination/filter parsing | Adopt shared input normalizers module-by-module |
| TD-007 | P2 | `Data`, `DTOs`, `Models`, `DomainModels` naming variance | Converge opportunistically without namespace breaks |
| TD-008 | P2 | Repeated frontend query keys/states/forms | Shared factories/components with visual regression tests |
| TD-009 | P2 | Compact one-line source in some modules | Format only alongside tests; no behavior redesign |
| TD-010 | P2 | Broad catches and silent fallback paths | Typed failures plus structured contextual logs |

Debt is prioritized by production risk, not aesthetics. Phase 2 testing and Phase 3 deployment work remain explicitly out of scope for this PR.
