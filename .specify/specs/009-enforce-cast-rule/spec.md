# Feature Specification: EnforceCastRule — transitive interface check + multi-class walking

**Feature Branch**: `009-enforce-cast-rule`
**Created**: 2026-05-01
**Status**: Draft

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Force Value Objects to declare a cast contract (Priority: P1)

As an architect, I want every concrete class under `\Models\ValueObjects\*` to honour the `Illuminate\Contracts\Database\Eloquent\CastsAttributes` contract, so that Laravel persists each Value Object through the cast pipeline rather than scattering serialisation logic across models.

**Why this priority**: Constitution Article IV — "Value Objects ... mapped to model columns via Laravel casts. They are never stored as raw JSON or arrays in the database." Casts implementing `CastsAttributes` are the contract Laravel uses for VO serialisation. P1.

**Acceptance Scenarios**:

1. **Caso positivo (true positive)** — **Given** a class `Opscale\Models\ValueObjects\Address` whose declaration neither implements `CastsAttributes` directly nor inherits it, **When** PHPStan analyses the file, **Then** the rule reports exactly one error.

2. **Caso negativo (true negative)** — **Given** a Value Object that implements `CastsAttributes` directly (`Opscale\Models\ValueObjects\ValidAddress`) AND a Value Object that inherits the contract from an abstract parent (`Opscale\Models\ValueObjects\InheritingValueObject` extends `AbstractCastableValueObject implements CastsAttributes`), **When** PHPStan analyses both files together with the parent, **Then** the rule reports zero errors.

---

### User Story 2 — Recognise inherited / transitive contracts (Priority: P2)

As a developer who shares Value Object behaviour through an abstract base or a contract interface, I expect the rule to find `CastsAttributes` whether it is declared directly on the class, inherited from a parent class, or extended through an intermediate interface. The check must be transitive.

**Why this priority**: This is the **false-positive** scenario. Today's rule reads only the literal `implements` clause of the leaf class. A perfectly valid `class Money extends AbstractCastableValueObject` that doesn't redeclare `implements CastsAttributes` is wrongly flagged. Closing this aligns the rule with how DDD codebases share contracts.

**Acceptance Scenarios**:

1. **Falso positivo a evitar** — **Given** an abstract parent `Opscale\Models\ValueObjects\AbstractCastableValueObject implements CastsAttributes` AND a concrete child `Opscale\Models\ValueObjects\InheritingValueObject extends AbstractCastableValueObject` (no inline `implements`), **When** PHPStan analyses the child along with the parent, **Then** the rule reports zero errors.

---

### User Story 3 — Walk every class declaration in a file (Priority: P2)

As an architect, I want the rule to inspect every class defined in a file under `\Models\ValueObjects\*`, not just the first one. A multi-class file whose first class is a valid VO and whose second class lacks the cast contract must still be flagged.

**Why this priority**: This is the **false-negative** scenario. Today's rule reads `getRootNode` which returns only the first `Class_` declaration; subsequent classes silently bypass the rule. Closing this restores coverage on multi-class files.

**Acceptance Scenarios**:

1. **Falso negativo a evitar** — **Given** a file `Opscale\Models\ValueObjects\MultiVOFile` whose first class implements `CastsAttributes` and whose second class does not, **When** PHPStan analyses the file, **Then** the rule reports exactly one error pointed at the second class declaration line.

---

### Edge Cases

- A class with no resolved namespacedName is skipped.
- An anonymous class is skipped.
- An interface, trait, or enum under `\Models\ValueObjects\*` is skipped (only concrete classes are subject to the rule).
- An **abstract** class under `\Models\ValueObjects\*` is skipped — abstract bases are infrastructure for VOs, not VOs themselves; concrete subclasses must satisfy the contract.
- A class implementing `MyContract extends CastsAttributes` is recognised as compliant — interface inheritance is walked.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The rule MUST extend `BaseRule` directly. `DomainRule`'s `\Models\*` gate is too broad; this rule needs the more specific `\Models\ValueObjects\*` gate.
- **FR-002**: `shouldProcess` MUST require the file's namespace to be under `\Models\ValueObjects\*`. The per-class shape filtering happens inside `validate`.
- **FR-003**: `validate` MUST iterate over every `Class_` node returned by `getClassNodes`. For each class:
  - Skip if there is no resolved `namespacedName`.
  - Skip if the class is abstract (abstract bases are exempt).
  - Skip if the class FQCN cannot be resolved by the reflection provider.
  - Use `ClassReflection::getInterfaces()` (which already walks parents and interface inheritance) to determine whether `Illuminate\Contracts\Database\Eloquent\CastsAttributes` is implemented.
  - Emit one error per non-conforming concrete class, at the class's own line number.
- **FR-004**: The error message MUST name the offending FQCN and the required interface FQCN.
- **FR-005**: Diagnostic identifier `ddd.valueObjects.enforceCast` MUST be preserved.

### Key Entities

- **EnforceCastRule** — `src/Rules/DDD/ValueObjects/EnforceCastRule.php`. Now extends `BaseRule`.
- **Address** fixture — existing positive case.
- **ValidAddress** fixture — existing direct-implements negative case.
- **AbstractCastableValueObject** + **InheritingValueObject** fixtures (new) — inheritance negative + false-positive demonstration.
- **MultiVOFile** fixture (new) — multi-class file false-negative case.

## Success Criteria *(mandatory)*

- **SC-001**: `vendor/bin/pest tests/Rules/EnforceCastTest.php` passes with the four named scenarios.
- **SC-002**: `npm test` continues all-green.
- **SC-003**: Diagnostic identifier remains `ddd.valueObjects.enforceCast`.

## Assumptions

- `ClassReflection::getInterfaces()` returns every interface implemented by the class, including those inherited from parents and those reached via interface extension. We rely on this to keep the rule expressive without manually walking interface chains.
- A multi-class file's classes can be loaded by composer if registered in `autoload-dev.classmap` (already used for `MultiClassFile.php` in feature 007). The new `MultiVOFile.php` follows the same pattern.
- Five legacy tests are consolidated into the four canonical scenarios. The dropped tests (`ignores_non_value_object_classes`, `detects_multiple_value_objects_in_different_files`, `ignores_classes_outside_value_objects_namespace`) are covered implicitly by `shouldProcess` and the positive case.
