# Feature Specification: InteractionLayerRule — allow MCP, Nova, Inertia, Sanctum, Log

**Feature Branch**: `011-interaction-layer-rule`
**Created**: 2026-05-01
**Status**: Draft
**Input**: User description: "para CLEAN agrega MCP en interaction"

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Block dependencies on unrelated lower-layer facades (Priority: P1)

As an architect, I want PHPStan to fail when an Interaction-layer class (a Controller, Console command, Nova Resource, Policy) imports a facade or framework class that belongs to a different layer's allowed set, so that the layer boundary stays intact.

**Why this priority**: Constitution Article III — "Layers ... communicate predictably". The rule already enforces this; the user's request relaxes the rule for a few clearly Interaction-shaped external libraries without weakening the rest. P1.

**Acceptance Scenarios**:

1. **Caso positivo (true positive)** — **Given** a Controller `Opscale\Http\Controllers\ProductController` that imports `Illuminate\Support\Facades\DB` (DB is a Representation-layer facade, not allowed in Interaction), **When** PHPStan analyses the file, **Then** the rule reports exactly one error.

2. **Caso negativo (true negative)** — **Given** a Controller that imports a mix of allowed Interaction facades (Auth, Validator, Log), allowed framework classes (Request, Response), an allowed external (Mcp\Server, Laravel\Nova\Resource, Inertia\Inertia, Laravel\Sanctum\Sanctum), AND a layer-3 Service from `\Opscale\Services\*`, **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 2 — Allow Interaction-shaped external libraries (Priority: P1)

As a developer building MCP servers, Nova admin panels, Inertia.js front-ends, or Sanctum-protected APIs, I expect the Interaction-layer rule to recognise these libraries' namespaces as legitimate external imports for the layer. Today the `allowedExternalImports` array is empty, so any of these libraries is wrongly flagged.

**Why this priority**: This is the **false-positive** scenario. MCP, Nova, Inertia, and Sanctum are all libraries whose entry points naturally live in the Interaction layer. Closing this lets developers build modern Laravel apps without disabling the rule.

**Acceptance Scenarios**:

1. **Falso positivo a evitar** — **Given** a Controller `Opscale\Http\Controllers\McpInertiaController` whose only imports are `Mcp\Server\ServerInterface`, `Laravel\Nova\Resource`, `Inertia\Inertia`, and `Laravel\Sanctum\Sanctum`, **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 3 — Don't widen the rule beyond the requested libraries (Priority: P2)

As an architect, I want the new external import allowance to be limited to the named libraries (MCP, Nova, Inertia, Sanctum). Other unrelated external libraries (e.g., a hypothetical `Foo\Hacker\` namespace) must continue to be flagged. Adding the new entries must not turn the rule into a wildcard.

**Why this priority**: This is the **false-negative** scenario "to avoid": the relaxation must not become a back door. A regression test pins down that everything else stays as it was.

**Acceptance Scenarios**:

1. **Falso negativo a evitar** — **Given** a Controller `Opscale\Http\Controllers\DbAccessController` that imports `Illuminate\Support\Facades\DB` (Representation-layer facade), **When** PHPStan analyses the file, **Then** the rule reports exactly one error. The expansion of `allowedExternalImports` did not accidentally permit Representation-only facades in Interaction.

---

### Edge Cases

- A Controller importing a layer-1 Model (`Opscale\Models\Foo`) is allowed via the project-import path (lower layer ≤ current).
- A Controller importing the bare `Mcp` (no subnamespace) is unusual; the prefix `Mcp\\` requires at least one segment after.
- The `Log` facade is universally useful across layers; adding it to Interaction does not affect other layers.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: `InteractionLayerRule` MUST extend its `allowedExternalImports` to `['Mcp\\', 'PhpMcp\\', 'Laravel\\Nova\\', 'Inertia\\', 'Laravel\\Sanctum\\']`.
- **FR-002**: `InteractionLayerRule` MUST extend its `allowedFacades` with `'Log'`.
- **FR-003**: No other allowed-list entries are added or removed in this feature. Other layers are out of scope.
- **FR-004**: The rule's diagnostic identifier `clean.layer5.importNotAllowed` MUST remain unchanged.

### Key Entities

- **InteractionLayerRule** — `src/Rules/CLEAN/Interaction/InteractionLayerRule.php`. Constructor argument tweak only.
- **ProductController** fixture — existing positive case (DB facade not allowed).
- **McpInertiaController** fixture (new) — proves the new allowances work.
- **DbAccessController** fixture (new) — falso-negativo regression guard.

## Success Criteria *(mandatory)*

- **SC-001**: `vendor/bin/pest tests/Rules/InteractionLayerTest.php` passes with the four named scenarios.
- **SC-002**: `npm test` continues all-green.
- **SC-003**: Diagnostic identifier remains `clean.layer5.importNotAllowed`.

## Assumptions

- The PHP MCP SDK roots most likely to appear are `Mcp\` (logiscape/mcp-sdk-php-style) and `PhpMcp\` (php-mcp/server-style). Both are added to keep the allowance ecosystem-neutral.
- `Laravel\Nova\` is the vendor namespace of the Laravel Nova SDK, distinct from the project's local `\Nova\` segment that already maps to layer 5.
- Adding `Log` to `allowedFacades` is purely additive; no existing test depends on `Log` being forbidden in Interaction.
- This change is **not breaking** for downstream consumers because allowed lists only grow — code that was previously flagged for using these libraries now passes; no code that previously passed now fails.
