# Feature Specification: ConditionalOverrideRule — skip magic methods, walk multi-class files

**Feature Branch**: `017-conditional-override-rule`
**Created**: 2026-05-01
**Status**: Draft

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Force public/protected methods to declare final or Override (Priority: P1)

As an architect, I want PHPStan to fail when a public or protected method is neither `final`, `abstract`, nor annotated with `#[\Override]`. The OCP requires methods to be either explicitly closed (final) or explicitly opened (Override) — never accidentally polymorphic.

**Why this priority**: Constitution Article VIII (OCP). P1.

**Acceptance Scenarios**:

1. **Caso positivo (true positive)** — **Given** a class `Opscale\Models\Product` whose method `isInStock()` is public, not final, not abstract, and not annotated with `#[\Override]`, **When** PHPStan analyses the file, **Then** the rule reports exactly one error.

2. **Caso negativo (true negative)** — **Given** a class `Opscale\Models\SimpleModel` with no public/protected methods (only `use` traits and properties), **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 2 — Skip magic methods (Priority: P1)

As a developer, I expect PHP magic methods (`__construct`, `__destruct`, `__toString`, `__invoke`, `__call`, `__get`, `__set`, ...) to be exempt from the final/Override requirement. These methods are not invoked through normal polymorphic dispatch — they have engine-level semantics and are conventionally not marked `final`.

**Why this priority**: This is the **false-positive** scenario. Today's rule flags any public/protected method without `final` or `Override`, including magic methods. Real codebases never declare `final __construct(...)` — that breaks `parent::__construct()` chaining in subclasses. Closing this matches PHP idiom.

**Acceptance Scenarios**:

1. **Falso positivo a evitar** — **Given** a class `Opscale\Models\MagicMethodsModel` whose only public methods are `__construct`, `__toString`, and `__invoke` (none marked `final`), **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 3 — Walk every class declaration in a file (Priority: P2)

As an architect, I want the rule to inspect every classlike declared in the file. Multi-class files are unusual but legal; today's rule only inspects the first class via `getRootNode`, so methods on any subsequent class are silently allowed to be non-final.

**Why this priority**: This is the **false-negative** scenario.

**Acceptance Scenarios**:

1. **Falso negativo a evitar** — **Given** a file `Opscale\Models\MultiClassConditionalOverride` declaring two classes — `FirstFinalClass` (all methods final) and `SecondNonFinalClass` (one public method without final or Override) — **When** PHPStan analyses the file, **Then** the rule reports exactly one error attributed to `SecondNonFinalClass`.

---

### Edge Cases

- Abstract methods stay exempt (cannot be final).
- Private methods stay exempt (not subject to polymorphic override).
- `__destruct`, `__call`, `__callStatic`, `__get`, `__set`, `__isset`, `__unset`, `__sleep`, `__wakeup`, `__serialize`, `__unserialize`, `__set_state`, `__clone`, `__debugInfo` — all exempt via the `__` prefix rule.
- A method whose name happens to start with `__` but is not a recognised PHP magic method is exempt by the same prefix rule. Convention reserves `__` for magic; nothing else should use it.

## Requirements *(mandatory)*

- **FR-001**: `ConditionalOverrideRule` MUST iterate every `Class_`, `Trait_`, and `Enum_` declaration in the file. Today's `getRootNode` is replaced with a multi-classlike walk.
- **FR-002**: Methods whose name starts with `__` (PHP magic methods) MUST be skipped. The check is `str_starts_with($methodName, '__')`.
- **FR-003**: Existing skip rules (private methods, abstract methods, final methods, `#[\Override]`-annotated methods) remain unchanged.
- **FR-004**: Diagnostic identifier `solid.ocp.conditionalOverride` MUST be preserved.
- **FR-005**: The rule remains package-agnostic; the regex used elsewhere does not gate this rule (it does not check the namespace at all).

## Success Criteria

- **SC-001**: `vendor/bin/pest tests/Rules/ConditionalOverrideTest.php` passes with the four named scenarios.
- **SC-002**: `npm test` continues all-green.
- **SC-003**: Diagnostic identifier remains `solid.ocp.conditionalOverride`.

## Assumptions

- Magic methods are conventionally exempt from `final`. Forcing `final __construct` would break parent-call chaining in subclasses, which is a common Eloquent pattern (e.g., overriding `__construct` to set defaults). Skipping them is the correct relaxation.
- Multi-class fixtures need a composer classmap entry.
