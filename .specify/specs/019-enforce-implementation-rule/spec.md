# Feature Specification: EnforceImplementationRule — transitive interface detection + multi-class walking

**Feature Branch**: `019-enforce-implementation-rule`
**Created**: 2026-05-01
**Status**: Draft

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Block stub implementations of interface methods (Priority: P1)

As an architect, I want PHPStan to fail when a class declares it implements an interface but provides only a stub body for one of its methods (empty, single-throw, single default-value return).

**Why this priority**: Constitution Article VIII (ISP). Half-implemented interfaces are a sign the interface is too broad and should be split. P1.

**Acceptance Scenarios**:

1. **Caso positivo (true positive)** — **Given** a class `Opscale\Services\BatchingService` implementing `Batchable` with three stub methods (`processBatch` returns empty array, `getBatchStatus` only throws, `completeBatch` has empty body), **When** PHPStan analyses the file, **Then** the rule reports three errors with the appropriate variant of the message.

2. **Caso negativo (true negative)** — **Given** a class `Opscale\Models\ValueObjects\ValidAddress` implementing `CastsAttributes` with substantive multi-statement bodies, **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 2 — Don't flag short but substantive method bodies (Priority: P1)

As a developer, I expect short interface-method implementations that delegate to helpers, return computed values, or perform side effects to be left alone. The rule should only flag the three explicit stub patterns: empty body, single throw, single default-value return.

**Why this priority**: This is the **false-positive guard**. The rule's tight stub definition is a deliberate design choice — a method with one statement that does real work (`return $this->compute();`, `Log::info(...)`, etc.) must NOT be flagged.

**Acceptance Scenarios**:

1. **Falso positivo a evitar** — **Given** a class `Opscale\Services\IntentionalShortMethodService` implementing `Batchable` whose every interface method has a single substantive statement (calling a helper, using a parameter), **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 3 — Detect stubs of interfaces inherited through a parent class (Priority: P2)

As an architect, I want PHPStan to flag stub implementations of interface methods that are inherited from a parent class. If `class Child extends Parent implements I {}` and `Parent implements I`, then `Child` is also bound by `I`'s contract — a stub override on `Child` is a violation regardless of whether `Child` directly declares `implements I`.

**Why this priority**: This is the **false-negative** scenario. Today's rule reads only `getInterfaceNodes` from the class's AST, which is the literal `implements` clause of the leaf class. Interfaces inherited via the parent class are missed.

**Acceptance Scenarios**:

1. **Falso negativo a evitar** — **Given** a parent class `Opscale\Models\ConcreteParentImplementer` that implements `Opscale\Contracts\InheritedContract`, **And** a child `Opscale\Models\StubChildOverrider extends ConcreteParentImplementer` that overrides the inherited interface method with a single-throw stub body, **When** PHPStan analyses the child file, **Then** the rule reports exactly one error.

---

### Edge Cases

- The rule walks every classlike (`Class_`, `Trait_`, `Enum_`) in the file — but Enums are skipped because their interface contract semantics differ.
- `ClassReflection::getInterfaces()` returns ALL interfaces (direct, inherited from parents, transitively via interface extension). The rule uses this single source.
- An abstract method inside a non-abstract class is also a stub but is skipped because PHP would fail at instantiation anyway; that error is the language's responsibility.

## Requirements *(mandatory)*

- **FR-001**: `EnforceImplementationRule` MUST iterate every `Class_` and `Trait_` declaration in the file (Enums skipped).
- **FR-002**: For each class, the rule MUST resolve the interface set via `ClassReflection::getInterfaces()` (transitive), not via the AST's direct `implements` clause.
- **FR-003**: The three stub patterns (empty body, single throw, single default-value return) remain the ONLY flagged shapes.
- **FR-004**: Diagnostic identifier `solid.isp.enforceImplementation` MUST be preserved.

## Success Criteria

- **SC-001**: `vendor/bin/pest tests/Rules/EnforceImplementationTest.php` passes with the four named scenarios.
- **SC-002**: `npm test` continues all-green.
- **SC-003**: Diagnostic identifier remains `solid.isp.enforceImplementation`.

## Assumptions

- Multi-class fixtures need an autoload-dev classmap entry; the new fixtures here are single-class so no entry needed.
- `ClassReflection::getInterfaces()` returns reflection objects for every interface the class is bound by, including transitively. The rule iterates them and collects public method names.
