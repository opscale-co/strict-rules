# Feature Specification: NoAccesorMutatorRule — tighten detection + multi-class walking

**Feature Branch**: `010-no-accesor-mutator-rule`
**Created**: 2026-05-01
**Status**: Draft

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Push attribute logic out of Models, into Value Objects (Priority: P1)

As an architect, I want PHPStan to fail when an Eloquent model under `\Models\*` defines:
- a Laravel-style mutator (`set<Name>Attribute`),
- a Laravel-style accessor (`get<Name>Attribute`), or
- a Laravel 9+ Attribute method that returns `Illuminate\Database\Eloquent\Casts\Attribute`,

so that custom transformation logic moves to a Value Object cast (a class implementing `CastsAttributes`) rather than being scattered across the Model.

**Why this priority**: Constitution Article IV — "Domain classes ... contain only declarative property definitions and cast configurations." Custom attribute logic belongs in `CastsAttributes` Value Objects, not in the Model itself. P1.

**Acceptance Scenarios**:

1. **Caso positivo (true positive)** — **Given** an Eloquent model `Opscale\Models\Product` defining `getIdAttribute()`, `setIdAttribute()`, AND `stock(): Attribute`, **When** PHPStan analyses the file, **Then** the rule reports exactly three errors at the corresponding method declaration lines.

2. **Caso negativo (true negative)** — **Given** an Eloquent model `Opscale\Models\ValidUlidUser` whose only methods are framework hooks like `casts()` (no accessors, no mutators, no Attribute methods), **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 2 — Don't flag overrides of Eloquent's own infrastructure methods (Priority: P2)

As a developer who legitimately overrides Eloquent's framework-level `getAttribute(string $key)` or `setAttribute(string $key, $value)` methods (for example, to add cross-cutting logging or to integrate with a custom attribute store), I expect the rule to leave those overrides alone. They are infrastructure overrides, not custom mutators on a specific attribute name.

**Why this priority**: This is the **false-positive** scenario. The current implementation flags any method whose name starts with `set`/`get` and ends with `Attribute` — including the bare `setAttribute` and `getAttribute` methods from Eloquent's `HasAttributes` trait. That mis-classifies a legitimate framework override as a mutator. Tightening the detection to require the canonical `set<Name>Attribute` / `get<Name>Attribute` shape (with at least the capitalised attribute name in between) closes this gap.

**Acceptance Scenarios**:

1. **Falso positivo a evitar** — **Given** an Eloquent model `Opscale\Models\InfrastructureOverrideModel` that overrides `getAttribute($key)` and `setAttribute($key, $value)` (the framework infrastructure methods, no custom attribute name in the method name), **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 3 — Walk every Eloquent model in a multi-class file (Priority: P2)

As an architect, I want the rule to inspect every class declared in a file under `\Models\*`, not just the first. A file whose first model is clean and whose second model defines a mutator must still be flagged on the second model.

**Why this priority**: This is the **false-negative** scenario. Today's rule reads `getRootNode` (first class only) — subsequent Eloquent classes silently bypass the check. Closing this restores coverage on multi-class files.

**Acceptance Scenarios**:

1. **Falso negativo a evitar** — **Given** a file `Opscale\Models\MultiModelWithMutator` declaring `FirstCleanModel extends Model` (no accessor / mutator / Attribute method) and `SecondModelWithMutator extends Model` (defines `getNameAttribute`), **When** PHPStan analyses the file, **Then** the rule reports exactly one error pointed at the second class's `getNameAttribute` line.

---

### Edge Cases

- A class with no resolved namespacedName is skipped.
- An anonymous class is skipped.
- A non-Eloquent class under `\Models\*` is skipped (only Eloquent subclasses are subject to the rule).
- A method whose return type name resolves to anything other than `Illuminate\Database\Eloquent\Casts\Attribute` (e.g., PHP's global `Attribute` class, or a custom `Foo\Attribute`) is **not** flagged.
- A method with a body returning `Attribute::make(...)` where `Attribute` resolves to anything other than the Laravel FQCN is **not** flagged.
- A method named `setAttribute` or `getAttribute` (Eloquent infrastructure overrides) is **not** flagged.
- A method named `getAttributes` (plural) is **not** flagged — already correctly excluded.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The rule MUST extend `BaseRule` directly. `shouldProcess` requires a FileNode whose namespace lies under `\Models\*`. The first-class-shape filtering moves into `validate`.
- **FR-002**: `validate` MUST iterate over every `Class_` returned by `getClassNodes`. For each class:
  - skip if no resolved namespacedName,
  - skip if the FQCN is not an Eloquent Model (subclass of `Illuminate\Database\Eloquent\Model`),
  - walk every `ClassMethod` and emit one error per method that is a custom mutator, accessor, or Attribute method.
- **FR-003**: A method is a mutator iff its name matches the regex `^set[A-Z]\w*Attribute$` (capital first letter then any word chars then literal `Attribute`).
- **FR-004**: A method is an accessor iff its name matches the regex `^get[A-Z]\w*Attribute$`.
- **FR-005**: A method is an Attribute method iff:
  - its declared return type, after PHPStan name resolution, is exactly `Illuminate\Database\Eloquent\Casts\Attribute`, OR
  - its body contains a top-level `return ...::make(...)` where the static call's class resolves to exactly `Illuminate\Database\Eloquent\Casts\Attribute`.
- **FR-006**: The rule MUST NOT use loose suffix checks (`str_ends_with(... '\\Attribute')`) or duplicate fallback string-prefix/suffix checks for mutator/accessor names.
- **FR-007**: Diagnostic identifier `ddd.valueObjects.noAccesorMutator` MUST be preserved.

### Key Entities

- **NoAccesorMutatorRule** — `src/Rules/DDD/ValueObjects/NoAccesorMutatorRule.php`. Now extends `BaseRule`.
- **Product** fixture — existing positive case.
- **ValidUlidUser** fixture — existing negative case.
- **InfrastructureOverrideModel** fixture (new) — false-positive guard for framework-method overrides.
- **MultiModelWithMutator** fixture (new) — multi-class file false-negative case.

## Success Criteria *(mandatory)*

- **SC-001**: `vendor/bin/pest tests/Rules/NoAccesorMutatorTest.php` passes with the four named scenarios.
- **SC-002**: `npm test` continues all-green.
- **SC-003**: Diagnostic identifier remains `ddd.valueObjects.noAccesorMutator`.

## Assumptions

- PHPStan's NameResolver resolves the `Attribute` symbol in code to its full FQCN before the rule sees it; `Name->toString()` is therefore the FQCN.
- Multi-class files require an `autoload-dev.classmap` entry in `composer.json` so the reflection provider can find both classes.
- A method named `setNameAttribute` is a custom mutator on the `name` attribute regardless of the method body; the rule does not need to inspect what the method does.
