# Feature Specification: ModelValidationRule — `Validatable` enforcement with full coverage

**Feature Branch**: `001-model-validation-rule`
**Created**: 2026-05-01
**Status**: Draft
**Input**: User description: "actualizar todas las reglas una por una; cada test debe incluir caso positivo, caso negativo, falso negativo y falso positivo; cada cambio en cada regla debe ser un nuevo feature; empecemos con ModelValidationRule. esta regla debe verificar que se implemente el trait validatable https://github.com/opscale-co/validations"

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Detect aggregate roots without `Validatable` (Priority: P1)

As an architect maintaining a Laravel codebase that follows Opscale's DDD guidelines, I want PHPStan to fail when an Eloquent model in `\Models` does not declare validation behaviour through the `Opscale\Validations\Validatable` trait, so that aggregate roots cannot be persisted without their business invariants being enforced.

**Why this priority**: This is the rule's core purpose. Without it, the rest of the architectural enforcement loses meaning: aggregate roots could mutate state without validation. P1.

**Independent Test**: Run `vendor/bin/pest tests/Rules/ModelValidationTest.php`. The "true positive" and "true negative" scenarios verify the rule fires on a model lacking the trait and stays silent on a model that uses it.

**Acceptance Scenarios**:

1. **Caso positivo (true positive)** — **Given** an Eloquent model `\Opscale\Models\Product` that does not use `Opscale\Validations\Validatable`, **When** PHPStan analyses the file, **Then** the rule reports exactly one error at the class declaration line stating that the class must use `Validatable` from `opscale-co/validations`.

2. **Caso negativo (true negative)** — **Given** an Eloquent model `\Opscale\Models\ValidatedModel` that uses `Opscale\Validations\Validatable`, **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 2 — Do not flag children of an already-validated parent (Priority: P2)

As a developer using inheritance to share a base aggregate-root behaviour (`AbstractParent extends Model { use Validatable; }`), I expect the rule to recognise that `Child extends AbstractParent` already satisfies the constraint through its parent, so I am not forced to redeclare `use Validatable;` in every concrete subclass.

**Why this priority**: This is the **false-positive** scenario. Today the rule only inspects the immediate class body, so a perfectly valid inheritance pattern is wrongly flagged. Fixing it removes a class of noise that would otherwise cause teams to disable the rule entirely.

**Independent Test**: A fixture pair (`ValidatedAggregateRoot` abstract parent + `ValidatedChild` concrete subclass with no inline trait) must produce zero errors.

**Acceptance Scenarios**:

1. **Falso positivo a evitar** — **Given** an abstract parent `AbstractValidatedAggregate extends Model` that uses `Opscale\Validations\Validatable`, **And** a concrete child `ValidatedChild extends AbstractValidatedAggregate` with no inline trait declaration, **When** PHPStan analyses both files, **Then** the rule reports zero errors for the child class.

---

### User Story 3 — Do not accept impostor traits with the same short name (Priority: P2)

As an architect, I want the rule to reject classes that use a trait literally named `Validatable` if it lives in a namespace other than `Opscale\Validations`. The trait must be the official one — a homonymous trait from another package must not satisfy the rule.

**Why this priority**: This is the **false-negative** scenario. The current implementation matches any trait whose short name ends in `\ValidatorTrait`, which lets impostors slip through. A model can silently bypass the validation contract by importing a same-named trait from anywhere. Fixing it closes a gap that defeats the rule's purpose.

**Independent Test**: A fixture model that uses `Opscale\Pretenders\Validatable` (a trait deliberately named the same as the official one but in a different namespace) must produce exactly one error.

**Acceptance Scenarios**:

1. **Falso negativo a evitar** — **Given** an Eloquent model `\Opscale\Models\PretendingValidatedModel` that imports `use Opscale\Pretenders\Validatable;` and declares `use Validatable;` inside the class body, **When** PHPStan analyses the file, **Then** the rule reports exactly one error stating that the class must use the official `Opscale\Validations\Validatable`.

---

### Edge Cases

- An abstract Eloquent model that itself uses `Validatable` must not be flagged.
- A `\Models` class that is NOT a subclass of `Illuminate\Database\Eloquent\Model` must not be analysed by this rule.
- Enums and interfaces in `\Models` must remain skipped (covered by `DomainRule::shouldProcess()` and `BaseRule::shouldProcess()`).
- Trait resolution must work whether the file imports the trait via `use Opscale\Validations\Validatable;` and then `use Validatable;` inside the class, or via the FQCN `use \Opscale\Validations\Validatable;` directly. Both forms are produced by PHPStan's NameResolver as the same FQCN.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The rule MUST emit a violation when an Eloquent model in `\Models` does not have the `Opscale\Validations\Validatable` trait reachable through itself or any ancestor class.
- **FR-002**: The rule MUST consider the inheritance chain. If any ancestor class (abstract or concrete) uses `Opscale\Validations\Validatable`, the leaf class is compliant.
- **FR-003**: The rule MUST match the trait by its fully-qualified name `Opscale\Validations\Validatable`. A trait whose short name is `Validatable` but whose FQCN is anything else MUST NOT satisfy the rule.
- **FR-004**: The rule MUST NOT analyse classes that are not subclasses of `Illuminate\Database\Eloquent\Model` or that do not live under a `\Models` namespace segment.
- **FR-005**: The error message MUST identify the offending class by its fully-qualified name and reference the official package `opscale-co/validations` and trait `Validatable` so that developers know what to install and what to declare.
- **FR-006**: The rule MUST keep its identifier `ddd.aggregates.modelValidation` so existing baseline files and ignore lists do not break.

### Key Entities

- **ModelValidationRule** — `src/Rules/DDD/Aggregates/ModelValidationRule.php`. Extends `DomainRule`. Inspects the AST + reflection of every Eloquent `\Models` class.
- **Validatable** — `Opscale\Validations\Validatable` trait from `opscale-co/validations`. The single accepted contract.
- **ValidatedModel** fixture — concrete class under `tests/fixtures/Models/` that uses `Validatable`. Demonstrates the "negative" case.
- **Product** fixture — concrete class under `tests/fixtures/Models/` that does NOT use `Validatable`. Demonstrates the "positive" case.
- **ValidatedAggregateRoot + ValidatedChild** fixtures (new) — abstract parent that uses `Validatable` plus a concrete child that does not redeclare it. Demonstrates the "false positive to avoid" case.
- **PretendingValidatedModel + Pretenders\Validatable** fixtures (new) — concrete class that uses a homonymous trait `Opscale\Pretenders\Validatable`. Demonstrates the "false negative to avoid" case.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: `vendor/bin/pest tests/Rules/ModelValidationTest.php` passes with at least four named scenarios: `caso_positivo`, `caso_negativo`, `falso_positivo_no_se_dispara`, `falso_negativo_se_dispara`.
- **SC-002**: `npm test` continues to be all-green after the rule change — no regression on the other 22 rule tests.
- **SC-003**: The rule's emitted error message contains the strings `Validatable` and `opscale-co/validations` so that developers reading PHPStan output can identify the package to install.
- **SC-004**: The PHPStan diagnostic identifier remains `ddd.aggregates.modelValidation`. No baseline drift in consumer projects.
- **SC-005**: `tests/fixtures/Models/ValidatedModel.php` is updated to use the official trait. No fixture references `Enigma\ValidatorTrait` after this feature.

## Assumptions

- The official trait FQCN is `Opscale\Validations\Validatable`, derived from the published composer.json of `opscale-co/validations` (`"Opscale\\Validations\\": "src/"`) and the file name `Validatable.php`. If upstream renames it, the rule and tests must change accordingly.
- PHPStan's NameResolver runs before the rule is invoked, so `$trait->toString()` on a `TraitUse` node returns the FQCN as imported. We rely on this for FQCN matching instead of parsing `use` statements manually.
- The library does NOT need to install `opscale-co/validations` as a real composer dependency to run the tests — the rule is AST-driven; fixture files only need to be syntactically valid PHP. Fixture trait declarations are local files that satisfy parsing without runtime resolution.
- The previous reference to `theriddleofenigma/laravel-model-validation` in `composer.json` require-dev was a stand-in for the now-canonical `opscale-co/validations`. It is replaced — not kept — by this feature so the codebase has a single source of truth.
