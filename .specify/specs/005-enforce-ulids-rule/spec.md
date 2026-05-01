# Feature Specification: EnforceUlidsRule — full trait scan, inheritance walk, disablement detection

**Feature Branch**: `005-enforce-ulids-rule`
**Created**: 2026-05-01
**Status**: Draft

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Block Eloquent models that lack ULID identity (Priority: P1)

As an architect, I want PHPStan to fail when an Eloquent model under `\Models\*` does not bring `Illuminate\Database\Eloquent\Concerns\HasUlids` into its trait set, so that domain entities use globally unique, sortable identifiers and never expose auto-increment primary keys.

**Why this priority**: Constitution Article IV — "Every entity uses a ULID as its primary key — not an auto-increment integer". P1.

**Acceptance Scenarios**:

1. **Caso positivo (true positive)** — **Given** an Eloquent model `Opscale\Models\User` whose class body declares no trait at all (or no `HasUlids`), and whose ancestors do not declare it either, **When** PHPStan analyses the file, **Then** the rule reports exactly one error stating that the class must use the `HasUlids` trait.

2. **Caso negativo (true negative)** — **Given** an Eloquent model `Opscale\Models\ValidUlidUser` that uses `HasUlids` directly **and** an Eloquent model `Opscale\Models\InheritingUlidEntity` that inherits `HasUlids` from an abstract parent `Opscale\Models\AbstractUlidEntity`, **When** PHPStan analyses both files, **Then** the rule reports zero errors.

---

### User Story 2 — Recognise `HasUlids` regardless of which trait-use statement declares it (Priority: P2)

As a Laravel developer who writes idiomatic models, I expect the rule to find `HasUlids` whether it lives in the first, second, or any later `use ...;` block inside the class body. A class with `use HasFactory, Notifiable;` followed by `use HasUlids;` is fully compliant.

**Why this priority**: This is the **false-positive** scenario. Today's implementation only inspects `$traitUse[0]->traits` — the first `use ...;` statement — which mis-flags a very common Laravel layout (one stmt per concern). Closing this removes constant noise on freshly-generated models.

**Acceptance Scenarios**:

1. **Falso positivo a evitar** — **Given** an Eloquent model `Opscale\Models\MultiStmtUlidModel` whose class body declares `use HasFactory, Notifiable;` followed by `use HasUlids;` as a separate statement, **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 3 — Reject classes that neutralise `HasUlids` via property overrides (Priority: P2)

As an architect, I want a class that declares `use HasUlids;` but explicitly sets `public $incrementing = true;` or `protected $keyType = 'int';` to be flagged. These overrides cancel the trait's contract — the class declares ULID intent on paper but persists with auto-increment integers in practice.

**Why this priority**: This is the **false-negative** scenario. Today's rule rubber-stamps any class that mentions `HasUlids` in its first trait-use, regardless of whether the class subsequently disables the trait. Closing this catches a real and common bug where developers leave the trait in place during partial migrations.

**Acceptance Scenarios**:

1. **Falso negativo a evitar** — **Given** an Eloquent model `Opscale\Models\DisabledUlidModel` that declares `use HasUlids;` and also `public $incrementing = true;` or `protected $keyType = 'int';`, **When** PHPStan analyses the file, **Then** the rule reports exactly one error stating that the trait is neutralised by property overrides and instructing the developer to remove them.

---

### Edge Cases

- A class whose only `HasUlids` reference comes from a transitively-used trait (a trait that itself uses `HasUlids`) is **not** recognised in v1. Trait-of-trait composition is rare in practice and is tracked separately.
- A class that sets `$keyType = 'string'` (the value `HasUlids` already sets) is fine — the override matches the trait's contract.
- A non-Model class is skipped (out-of-scope handled by `DomainRule::shouldProcess` + `isEloquentModel`).
- Multiple traits in one statement (`use HasFactory, Notifiable, HasUlids;`) — the rule iterates every trait in every stmt, so order-within-stmt does not matter.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The rule MUST inspect every `TraitUse` statement in the class body (not just the first) when looking for `HasUlids`.
- **FR-002**: The rule MUST walk the inheritance chain via `ClassReflection::getParents()` and consider `HasUlids` satisfied if any ancestor declares it as a direct trait.
- **FR-003**: When `HasUlids` is satisfied (directly or via inheritance), the rule MUST additionally inspect the class's own properties and emit an error when:
  - A `incrementing` property is declared with a default value that is the literal `true`.
  - A `keyType` property is declared with a non-`'string'` string default.
- **FR-004**: The rule MUST emit two distinct messages:
  - **Missing**: "Model class \"X\" must use the \"HasUlids\" trait to ensure consistent ID handling with ULIDs."
  - **Disabled**: "Model class \"X\" uses the \"HasUlids\" trait but explicitly disables it via property overrides ($incrementing or $keyType). Remove these overrides so ULID identity remains consistent."
- **FR-005**: Diagnostic identifier `ddd.entities.enforceUlids` MUST be preserved.

### Key Entities

- **EnforceUlidsRule** — `src/Rules/DDD/Entities/EnforceUlidsRule.php`. Extends `DomainRule`. The change is internal; signature unchanged.
- **User** fixture — existing positive case.
- **ValidUlidUser** fixture — existing direct-use negative.
- **AbstractUlidEntity** + **InheritingUlidEntity** fixtures (new) — inheritance negative.
- **MultiStmtUlidModel** fixture (new) — multi-statement false-positive.
- **DisabledUlidModel** fixture (new) — disablement false-negative.

## Success Criteria *(mandatory)*

- **SC-001**: `vendor/bin/pest tests/Rules/EnforceUlidsTest.php` passes with the four named scenarios.
- **SC-002**: `npm test` continues all-green after the change.
- **SC-003**: Diagnostic identifier remains `ddd.entities.enforceUlids`.
- **SC-004**: The disablement message is distinct and references the offending property names.

## Assumptions

- PHPStan's `NameResolver` runs before the rule, so `use HasUlids;` (after a file-level `use Illuminate\Database\Eloquent\Concerns\HasUlids;`) and `use \Illuminate\Database\Eloquent\Concerns\HasUlids;` both produce a `Name` whose `toString()` is the FQCN.
- `ClassReflection::getParents()` returns parents in order from immediate to root.
- `getNativeReflection()->getTraitNames()` lists DIRECT traits per class. Transitive trait composition is not walked in v1.
- The legacy tests `ignores_non_model_classes`, `detects_multiple_models_in_same_file`, and `allows_model_inherits_from_custom_base` are merged into the four canonical scenarios — the first is implicit in `DomainRule::shouldProcess`, the second is a permutation of the positive case, and the third has the same expected outcome as the positive case.
