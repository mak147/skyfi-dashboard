# Phase 1 Architecture Audit

**Reviewed:** 2026-07-15  
**Scope:** 1,445 tracked files; 846 PHP, 88 TypeScript, 391 TSX, 33 SQL migrations, 61 existing architecture documents, and every completed functional module.

## Executive assessment

SkyFi is a modular monolith with a PHP 8.2 REST API and a React 18 SPA. Backend modules generally use Controller → Service → Repository boundaries, contracts, DTOs, and PDO. The frontend is organized by feature and uses TanStack Query for server state and Redux only for authentication. This is a sound deployment unit for v1 and should not be decomposed into services before operational evidence justifies it.

## Findings

| Priority | Finding | Disposition |
|---|---|---|
| P0 | Password reset credentials were returned by the API regardless of environment | Fixed: explicit, default-off local opt-in |
| P1 | Composition root is a 1,300+ line eager constructor | Incremental provider extraction; do not rewrite atomically |
| P1 | Pagination parsing and limits differ between modules | Shared normalizer introduced and adopted by Connections/Monitoring as reference |
| P1 | Several services/repositories exceed 500–850 lines | Split by use case/query in later bounded changes with characterization tests |
| P1 | Finance repository contract resolved to a cloned instance | Fixed: contract and implementation share one singleton |
| P2 | DTO naming varies between `Data` and `DTOs`; compact/minified source exists | Standardize only when touching modules; avoid namespace-breaking moves |
| P2 | Route files consistently resolve dependencies but authorization is partly controller-level | Prefer route middleware for new/refactored endpoints while retaining behavior |
| P2 | Frontend query keys and loading/error shells are repeated | Introduce feature query-key factories and shared async states incrementally |

## Target boundaries

- Controllers translate HTTP input/output only.
- DTOs normalize and validate untrusted input.
- Services own transactions and business orchestration.
- Repositories own SQL and hydration, returning domain data rather than HTTP shapes.
- Cross-module work uses contracts/events; modules must not instantiate another module's concrete repository.
- Redux remains client/session state; TanStack Query remains server state.

## Compatibility policy

Existing paths, payload envelopes, legacy `page`/`per_page`, JSON:API pagination, and constructor call sites remain supported. Refactoring must be test-backed and independently reversible.
