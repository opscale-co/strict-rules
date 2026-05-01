# Feature Specification: ParentCallRule — walk multi-class files

**Feature Branch**: `018-parent-call-rule`
**Created**: 2026-05-01
**Status**: Draft

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Block overrides that drop parent behaviour (Priority: P1)

As an architect, I want PHPStan to fail when an instance method overrides a concrete parent method without calling `parent::`. The Liskov Substitution Principle requires subclass behaviour to be compatible with the base; calling `parent::` preserves the contract unless the override is intentionally a complete replacement.

**Why this priority**: Constitution Article VIII (LSP). P1.

**Acceptance Scenarios**:

1. **Caso positivo (true positive)** — **Given** a class `Opscale\Services\BatchingService` whose `canBatch()` overrides a parent method without calling `parent::canBatch()`, **When** PHPStan analyses the file, **Then** the rule reports exactly one error.

2. **Caso negativo (true negative)** — **Given** a class `Opscale\Models\ProperOverrider` extending `AbstractParentModel` whose every override calls `parent::`, **When** PHPStan analyses both files, **Then** the rule reports zero errors.

---

### User Story 2 — Don't flag implementations of abstract parent methods (Priority: P1)

As a developer, I expect PHPStan to skip methods that are implementing (not overriding) an abstract parent method. There is no parent body to call — the abstract method has none.

**Why this priority**: This is the **false-positive guard**. Today's rule already handles it correctly through `extendedMethodReflection->isAbstract()`. The four-scenario contract pins this behaviour so it doesn't regress when the rule is touched.

**Acceptance Scenarios**:

1. **Falso positivo a evitar** — **Given** a class `Opscale\Models\AbstractImplementer` extending `AbstractParentModel` whose only method `getName()` implements an abstract parent method (no `parent::` call possible), **When** PHPStan analyses both files, **Then** the rule reports zero errors.

---

### User Story 3 — Walk every class declaration in a file (Priority: P2)

As an architect, I want the rule to inspect every classlike declared in the file. Multi-class files are unusual but legal; today's rule only inspects the first class via `getRootNode`, so violating overrides on subsequent classes slip through.

**Why this priority**: This is the **false-negative** scenario.

**Acceptance Scenarios**:

1. **Falso negativo a evitar** — **Given** a file `Opscale\Models\MultiClassParentCall` declaring two classes — `FirstProperOverrider extends AbstractParentModel` (override calls `parent::`) and `SecondImproperOverrider extends AbstractParentModel` (override does NOT call `parent::`) — **When** PHPStan analyses the file, **Then** the rule reports exactly one error attributed to `SecondImproperOverrider`.

---

### Edge Cases

- Static methods are skipped (LSP applies to instance polymorphism).
- Methods on classes without an `extends` clause are skipped (`shouldProcess` short-circuits).
- A `parent::method()` call anywhere in the method body counts (NodeFinder traverses recursively, including inside `try`/`catch`, conditionals, closures bound to `$this`).
- An override of a private parent method does not count — private methods cannot be inherited (they get re-declared).

## Requirements *(mandatory)*

- **FR-001**: `ParentCallRule` MUST iterate every `Class_`, `Trait_`, and `Enum_` declaration in the file. Today's `getRootNode` is replaced with a multi-classlike walk.
- **FR-002**: Per-class, the rule MUST resolve `ClassReflection` via the FQCN and use `getParentClass()` to get the parent's reflection. Skip classlikes that have no parent.
- **FR-003**: Existing skips remain: static methods, abstract parent methods, private parent methods, methods with `parent::` calls anywhere in their body.
- **FR-004**: Diagnostic identifier `solid.lsp.parentCall` MUST be preserved.

## Success Criteria

- **SC-001**: `vendor/bin/pest tests/Rules/ParentCallTest.php` passes with the four named scenarios.
- **SC-002**: `npm test` continues all-green.
- **SC-003**: Diagnostic identifier remains `solid.lsp.parentCall`.

## Assumptions

- Multi-class fixtures need an autoload-dev classmap entry.
- The constitution's "unless documented as replacement" exemption is out of scope for this feature; the rule remains strict by default. Adding an annotation-based exemption is tracked separately.
