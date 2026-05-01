# Feature Specification: CommunicationLayerRule — allow Eloquent typing and Log

**Feature Branch**: `012-communication-layer-rule`
**Created**: 2026-05-01
**Status**: Draft
**Input**: User description: "corrige los allowed para cada capa según sea necesario"

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Block forbidden upward dependencies (Priority: P1)

As an architect, I want PHPStan to fail when a Communication-layer class (an Observer) imports a class from a higher layer (Orchestration / Interaction) or a facade reserved for a different layer, so that the layer boundary stays intact.

**Why this priority**: Constitution Article III — events flow upward via dispatch, NOT via direct import. Observers should remain thin event-emitters. P1.

**Acceptance Scenarios**:

1. **Caso positivo (true positive)** — **Given** an Observer `Opscale\Observers\ProductObserver` that imports `Illuminate\Support\Facades\Response` (an Interaction-layer facade) and `Opscale\Jobs\CleanOldProducts` (a layer-4 Job), **When** PHPStan analyses the file, **Then** the rule reports two errors.

2. **Caso negativo (true negative)** — **Given** an Observer that imports a project Model (lower layer), an Eloquent Model type-hint, the `Log` / `Event` / `Broadcast` facades, and an `Illuminate\Contracts\Broadcasting\ShouldBroadcast` contract, **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 2 — Allow Eloquent type hints in Observer methods (Priority: P1)

As a developer writing Observers, my method signatures naturally type-hint the affected Model. The rule should not flag the framework-level `Illuminate\Database\Eloquent\Model` (or its subclasses imported via the framework) when used purely for typing or extending.

**Why this priority**: This is the **false-positive** scenario. Today's Communication rule allows only `Illuminate\Contracts\Broadcasting\` and `Illuminate\Events\` framework imports. An Observer that wants to type-hint or extend a framework-level Eloquent class is wrongly flagged. Closing this matches how Observers are actually written.

**Acceptance Scenarios**:

1. **Falso positivo a evitar** — **Given** an Observer `Opscale\Observers\ValidObserver` whose only imports are `Illuminate\Database\Eloquent\Model` (for type hints), `Illuminate\Support\Facades\Log`, `Illuminate\Support\Facades\Event`, `Illuminate\Support\Facades\Broadcast`, and a project Model from `\Opscale\Models\*`, **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 3 — Don't widen the rule beyond what's intended (Priority: P2)

As an architect, I want the new allowances (Eloquent framework typing, Log facade) to be additive only. Other forbidden facades (e.g., `DB`, reserved for Representation) must continue to be flagged in Observers.

**Why this priority**: This is the **false-negative** scenario "to avoid": the relaxation must not become a back door. A regression guard pins the rule's intent.

**Acceptance Scenarios**:

1. **Falso negativo a evitar** — **Given** an Observer `Opscale\Observers\DbAccessObserver` that imports `Illuminate\Support\Facades\DB`, **When** PHPStan analyses the file, **Then** the rule reports exactly one error.

---

### Edge Cases

- The rule remains package-agnostic — `Opscale\Observers\Foo` and `App\Observers\Foo` and `Vendor\Package\Observers\Foo` are all detected as layer 2 by the regex `^(\w+)(\\\w+)*(\\Observers\\)`.
- Adding `Illuminate\Database\Eloquent\` does NOT promote project Models (layer 1) — those are still routed through the project-layer check.
- The `Log` facade is universally useful in Observers for state-change tracing.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: `CommunicationLayerRule` MUST extend `allowedFrameworkImports` with `'Illuminate\\Database\\Eloquent\\'`.
- **FR-002**: `CommunicationLayerRule` MUST extend `allowedFacades` with `'Log'`.
- **FR-003**: No removals from any allowed list. No changes to `allowedExternalImports` (stays empty).
- **FR-004**: Diagnostic identifier `clean.layer2.importNotAllowed` MUST remain unchanged.
- **FR-005**: The rule MUST stay package-agnostic — fixtures use the project's `Opscale\` root, but the rule's regex accepts any first-segment word.

### Key Entities

- **CommunicationLayerRule** — `src/Rules/CLEAN/Communication/CommunicationLayerRule.php`. Constructor argument tweak only.
- **ProductObserver** fixture — existing positive case.
- **ValidObserver** fixture (new) — proves the new allowances work; `Opscale\Observers` namespace.
- **DbAccessObserver** fixture (new) — falso-negativo regression guard; `Opscale\Observers` namespace.

## Success Criteria *(mandatory)*

- **SC-001**: `vendor/bin/pest tests/Rules/CommunicationLayerTest.php` passes with the four named scenarios.
- **SC-002**: `npm test` continues all-green.
- **SC-003**: Diagnostic identifier remains `clean.layer2.importNotAllowed`.

## Assumptions

- An Observer needing the framework `Illuminate\Database\Eloquent\Model` for type hints is the canonical Laravel idiom.
- Logging from Observers is a well-established pattern; `Log` is universally needed.
- This change is **not breaking** for downstream consumers (allowed lists only grow).
