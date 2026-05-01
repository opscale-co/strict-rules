# Feature Specification: OrchestrationLayerRule — allow Eloquent typing and Log

**Feature Branch**: `013-orchestration-layer-rule`
**Created**: 2026-05-01
**Status**: Draft
**Input**: User description: "corrige los allowed para cada capa según sea necesario"

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Block forbidden upward dependencies (Priority: P1)

As an architect, I want PHPStan to fail when an Orchestration-layer class (a Job or Notification) imports a class from a higher layer (Interaction) or a facade reserved for a different layer, so that the layer boundary stays intact.

**Why this priority**: Constitution Article III — Jobs and Notifications coordinate work but never depend on Controllers or HTTP-layer constructs. P1.

**Acceptance Scenarios**:

1. **Caso positivo (true positive)** — **Given** a Job `Opscale\Jobs\CleanOldProducts` that imports `Opscale\Http\Controllers\ProductsController` (a layer-5 class) and `Illuminate\Support\Facades\Http` (an Interaction-layer facade), **When** PHPStan analyses the file, **Then** the rule reports two errors.

2. **Caso negativo (true negative)** — **Given** a Job that imports the standard Bus / Queue framework helpers, an Eloquent Model type-hint, the `Log` and `Bus` facades, and a project Model from `\Opscale\Models\*`, **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 2 — Allow Eloquent type hints in Jobs and Notifications (Priority: P1)

As a developer, my Jobs and Notifications often type-hint Eloquent Models that are dispatched into them (`new MyJob(User $user)`, `notify(new MyNotification(Product $product))`). The rule should not flag the framework-level `Illuminate\Database\Eloquent\Model` (or its subclasses imported via the framework) when used purely for typing.

**Why this priority**: This is the **false-positive** scenario. Today's Orchestration rule allows Bus, Queue, Mail, Notifications, but NOT `Illuminate\Database\Eloquent\`. A Job that wants to type-hint a Model parameter is wrongly flagged. Closing this matches how Jobs are actually written.

**Acceptance Scenarios**:

1. **Falso positivo a evitar** — **Given** a Job `Opscale\Jobs\ValidJob` whose only imports are `Illuminate\Bus\Queueable`, `Illuminate\Contracts\Queue\ShouldQueue`, `Illuminate\Foundation\Bus\Dispatchable`, `Illuminate\Queue\SerializesModels`, `Illuminate\Database\Eloquent\Model` (for type hints), `Illuminate\Support\Facades\Log`, and a project Model from `\Opscale\Models\*`, **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 3 — Don't widen the rule beyond what's intended (Priority: P2)

As an architect, I want the new allowances to be additive only. Other forbidden facades (e.g., `DB`, reserved for Representation) must continue to be flagged in Jobs.

**Why this priority**: This is the **false-negative** scenario "to avoid": the relaxation must not become a back door. A regression guard pins the rule's intent.

**Acceptance Scenarios**:

1. **Falso negativo a evitar** — **Given** a Job `Opscale\Jobs\DbAccessJob` that imports `Illuminate\Support\Facades\DB`, **When** PHPStan analyses the file, **Then** the rule reports exactly one error.

---

### Edge Cases

- The rule remains package-agnostic — `Opscale\Jobs\Foo`, `App\Jobs\Foo`, and `Vendor\Package\Jobs\Foo` all detect as layer 4 by the regex `^(\w+)(\\\w+)*(\\Jobs\\)`.
- `Notifications` classes use the same rule (same layer 4); the new allowances apply to both.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: `OrchestrationLayerRule` MUST extend `allowedFrameworkImports` with `'Illuminate\\Database\\Eloquent\\'`.
- **FR-002**: `OrchestrationLayerRule` MUST extend `allowedFacades` with `'Log'`.
- **FR-003**: No removals from any allowed list. No changes to `allowedExternalImports` (stays empty).
- **FR-004**: Diagnostic identifier `clean.layer4.importNotAllowed` MUST remain unchanged.
- **FR-005**: The rule MUST stay package-agnostic — fixtures use `Opscale\` root, but the rule's regex accepts any first-segment word.

### Key Entities

- **OrchestrationLayerRule** — `src/Rules/CLEAN/Orchestration/OrchestrationLayerRule.php`. Constructor argument tweak only.
- **CleanOldProducts** fixture — existing positive case (already imports a Controller and Http facade).
- **ValidJob** fixture (new) — proves the new allowances work; `Opscale\Jobs` namespace.
- **DbAccessJob** fixture (new) — falso-negativo regression guard; `Opscale\Jobs` namespace.

## Success Criteria *(mandatory)*

- **SC-001**: `vendor/bin/pest tests/Rules/OrchestrationLayerTest.php` passes with the four named scenarios.
- **SC-002**: `npm test` continues all-green.
- **SC-003**: Diagnostic identifier remains `clean.layer4.importNotAllowed`.

## Assumptions

- Jobs and Notifications routinely receive Eloquent Models. Allowing the framework Eloquent namespace covers Model and its base classes used as type hints or for `SerializesModels` payloads.
- Logging from Jobs is universal; `Log` is the standard facade.
- This change is **not breaking** for downstream consumers (allowed lists only grow).
