# Feature Specification: TransformationLayerRule — allow Support helpers and Log

**Feature Branch**: `014-transformation-layer-rule`
**Created**: 2026-05-01
**Status**: Draft

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Block forbidden upward dependencies (Priority: P1)

As an architect, I want PHPStan to fail when a Transformation-layer class (a Service, Exception, or Contract) imports a class from a higher layer (Orchestration / Interaction) or a facade reserved for a different layer.

**Acceptance Scenarios**:

1. **Caso positivo (true positive)** — **Given** an `Opscale\Services\ExternalAPIService` that imports the `Response` facade (Interaction-layer) and `Opscale\Jobs\CleanOldProducts` (layer 4), **When** PHPStan analyses the file, **Then** the rule reports two errors.

2. **Caso negativo (true negative)** — **Given** a Service that imports `Illuminate\Support\Collection`, `Illuminate\Support\Str`, the `Log` and `Cache` facades, plus a project Model, **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 2 — Allow Illuminate\Support helpers in Services (Priority: P1)

As a developer writing Services, my code commonly uses `Illuminate\Support\Collection`, `Illuminate\Support\Str`, and `Illuminate\Support\Arr` for transforming and shaping data. The rule should not flag these — they are the canonical Laravel data utilities.

**Why this priority**: This is the **false-positive** scenario. Today's Transformation rule allows Contracts, Foundation, Symfony components, and Http\Client, but not the Support helpers. A Service that uses Collection / Str / Arr is wrongly flagged. Closing this matches how Services are written.

**Acceptance Scenarios**:

1. **Falso positivo a evitar** — **Given** an `Opscale\Services\ServiceUsingHelpers` that imports `Illuminate\Support\Collection`, `Illuminate\Support\Str`, `Illuminate\Support\Arr`, and `Log`, **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 3 — Don't widen the rule beyond what's intended (Priority: P2)

**Acceptance Scenarios**:

1. **Falso negativo a evitar** — **Given** an `Opscale\Services\DbAccessService` that imports `Illuminate\Support\Facades\DB` (Representation-only), **When** PHPStan analyses the file, **Then** the rule reports exactly one error.

---

### Edge Cases

- The rule remains package-agnostic — `Opscale\Services\Foo`, `App\Services\Foo`, etc. all detect as layer 3.
- `Illuminate\Support\Facades\` is NOT auto-allowed by adding `Illuminate\Support\` because facades go through the separate `isAllowedFacade` check that compares against `allowedFacades` only.

## Requirements *(mandatory)*

- **FR-001**: `TransformationLayerRule` MUST extend `allowedFrameworkImports` with the specific Support helper classes `Illuminate\\Support\\Arr`, `Illuminate\\Support\\Collection`, `Illuminate\\Support\\Number`, `Illuminate\\Support\\Str`. Adding the broader prefix `Illuminate\\Support\\` MUST be avoided because `str_starts_with` would then accidentally allow `Illuminate\\Support\\Facades\\X` and bypass the facade gate.
- **FR-002**: `TransformationLayerRule` MUST extend `allowedFacades` with `'Log'`.
- **FR-003**: No removals from any allowed list.
- **FR-004**: Diagnostic identifier `clean.layer3.importNotAllowed` MUST remain unchanged.
- **FR-005**: Adding `Illuminate\Support\` MUST NOT bypass the facade check — facades are still gated by `allowedFacades`.

## Success Criteria

- **SC-001**: `vendor/bin/pest tests/Rules/TransformationLayerTest.php` passes with the four named scenarios.
- **SC-002**: `npm test` continues all-green.
- **SC-003**: Diagnostic identifier remains `clean.layer3.importNotAllowed`.

## Assumptions

- The CleanRule's `isAllowedFacade` check runs before `isAllowedFrameworkUse`. So `Illuminate\Support\Facades\DB` is checked against `allowedFacades` first; only `Illuminate\Support\Foo` (non-facade Support classes) match the new framework allowance.
