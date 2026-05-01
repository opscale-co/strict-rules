# Feature Specification: HelpersRestrictionRule — walk multi-class files

**Feature Branch**: `023-helpers-restriction-rule`
**Created**: 2026-05-01
**Status**: Draft

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Block Laravel helper-function usage (Priority: P1)

As an architect, I want PHPStan to fail when a class uses Laravel's global helper functions (`auth()`, `cache()`, `config()`, ...). Helpers obscure dependencies; the constitution requires explicit DI or facades.

**Why this priority**: Constitution Article VIII (DIP) — "Never instantiate dependencies with `new ClassName()` inside a class body. All dependencies are injected via constructor." Helpers are the same anti-pattern in another shape. P1.

**Acceptance Scenarios**:

1. **Caso positivo (true positive)** — **Given** a class `Opscale\Classes\ClassWithHelpers` that uses `auth()->user()`, `cache()->get('key')`, and `config('app.name')`, **When** PHPStan analyses the file, **Then** the rule reports exactly three errors.

2. **Caso negativo (true negative)** — **Given** a class `Opscale\Classes\ClassWithoutHelpers` that uses constructor-injected services and no helper calls, **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 2 — Don't flag static calls or facade calls that resemble helpers (Priority: P1)

As a developer using facades (`Auth::user()`) or static methods on classes (`MyClass::doThing()`), I expect the rule to leave them alone. The rule's intent is to flag GLOBAL FUNCTION calls — not method calls on classes.

**Why this priority**: This is the **false-positive guard**. Today's rule detects `FuncCall` nodes specifically, so static calls and facade calls (which are `StaticCall` nodes) are correctly NOT flagged. The four-scenario contract pins this behaviour.

**Acceptance Scenarios**:

1. **Falso positivo a evitar** — **Given** a class `Opscale\Classes\ClassWithStaticMethods` whose method bodies contain only static calls (`Auth::user()`, `Cache::get()`, `MyClass::create()`), **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 3 — Walk every classlike in the file (Priority: P2)

As an architect, I want the rule to inspect every classlike declared in the file. Multi-class files are unusual but legal; today's rule only inspects the first class via `getRootNode`, so helper usage in subsequent classes slips through.

**Why this priority**: This is the **false-negative** scenario.

**Acceptance Scenarios**:

1. **Falso negativo a evitar** — **Given** a file `Opscale\Classes\MultiClassWithHelpers` declaring two classes — `FirstCleanClass` (DI only) and `SecondHelperClass` (uses `cache()->get('key')`) — **When** PHPStan analyses the file, **Then** the rule reports exactly one error attributed to the second class.

---

### Edge Cases

- The hardcoded helper list covers Laravel's global functions: `auth`, `cache`, `config`, `session`, `request`, `response`, `route`, `url`, `view`, `app`, `collect`, `logger`, `storage`, `validator`, `cookie`, `redirect`, `back`, `old`, `csrf_token`, `csrf_field`, `method_field`, `trans`, `__`, `trans_choice`, `policy`, `rescue`, `retry`, `tap`, `throw_if`, `throw_unless`, `with`, `broadcast`, `dispatch`, `event`, `factory`, `info`, `logs`, `now`, `optional`, `report`, `resolve`, `today`, `yesterday`.
- PHP built-in functions (`strlen`, `array_map`, ...) are not in the helper list and never get flagged.
- Both standalone calls (`config('foo')`) and chained calls (`cache()->get('key')`) are detected.

## Requirements *(mandatory)*

- **FR-001**: `HelpersRestrictionRule` MUST iterate every `Class_`, `Trait_`, and `Enum_` declared in the file.
- **FR-002**: All existing detection (chained method calls and standalone helper calls) is preserved.
- **FR-003**: Diagnostic identifier `smells.helpersRestriction.helper` MUST be preserved.

## Success Criteria

- **SC-001**: `vendor/bin/pest tests/Rules/HelpersRestrictionTest.php` passes with the four named scenarios.
- **SC-002**: `npm test` continues all-green.
- **SC-003**: Diagnostic identifier remains `smells.helpersRestriction.helper`.

## Assumptions

- Multi-class fixtures need an `autoload-dev.classmap` entry.
