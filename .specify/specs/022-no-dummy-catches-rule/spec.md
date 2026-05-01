# Feature Specification: NoDummyCatchesRule — allow wrapping throws + multi-class walking

**Feature Branch**: `022-no-dummy-catches-rule`
**Created**: 2026-05-01
**Status**: Draft

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Block dummy catch blocks (Priority: P1)

As an architect, I want PHPStan to fail on `try`/`catch` blocks whose catch body is empty, only `return`s, or only `throw`s the same exception. These are dummy catches that lose information and break failure handling.

**Why this priority**: Constitution Article VIII (SOLID) and the broader Smells category. P1.

**Acceptance Scenarios**:

1. **Caso positivo (true positive)** — **Given** a class `Opscale\Jobs\CleanOldProducts` with a `catch (\Exception $e) { }` block (empty body), **When** PHPStan analyses the file, **Then** the rule reports exactly one error.

2. **Caso negativo (true negative)** — **Given** a class `Opscale\Jobs\ValidExceptionHandling` whose catch block contains substantive logic (logging, conditional handling), **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 2 — Don't flag exception wrapping (Priority: P1)

As a developer, I want the rule to leave alone catch blocks that wrap the original exception in a different exception type — `catch (\Exception $e) { throw new BusinessException('context', 0, $e); }`. Wrapping is the canonical way to add context and preserve the original cause via `$e`. It is NOT a dummy catch — it transforms the exception type and adds information.

**Why this priority**: This is the **false-positive** scenario. Today's rule treats every single-throw catch as a dummy, including legitimate wraps. Closing this matches PHP exception-handling idiom.

**Acceptance Scenarios**:

1. **Falso positivo a evitar** — **Given** a class `Opscale\Jobs\WrappingExceptionJob` whose catch block contains a single `throw new RuntimeException('wrapped: '.$e->getMessage(), 0, $e);`, **When** PHPStan analyses the file, **Then** the rule reports zero errors. The throw expression's argument is a `New_` — recognised as wrapping, not rethrowing.

---

### User Story 3 — Walk every classlike in the file (Priority: P2)

As an architect, I want the rule to inspect every classlike declared in the file. Multi-class files are unusual but legal; today's rule only inspects the first class via `getRootNode`, so dummy catches in subsequent classes slip through.

**Why this priority**: This is the **false-negative** scenario.

**Acceptance Scenarios**:

1. **Falso negativo a evitar** — **Given** a file `Opscale\Jobs\MultiClassDummyCatch` declaring two classes — `FirstHandledJob` (catch with logging) and `SecondDummyJob` (empty catch) — **When** PHPStan analyses the file, **Then** the rule reports exactly one error attributed to the second class.

---

### Edge Cases

- A catch with a single throw of an arbitrary expression (e.g., `throw $factory->makeException($e)`) is still flagged: only `throw new Class(...)` counts as wrapping.
- Rethrowing the same variable (`throw $e`) is still flagged — it adds nothing.
- A catch with `return ...;` only is still flagged.
- An empty catch is still flagged.

## Requirements *(mandatory)*

- **FR-001**: `NoDummyCatchesRule` MUST iterate every `Class_`, `Trait_`, and `Enum_` declared in the file.
- **FR-002**: A catch block whose body is a single `throw new SomeClass(...)` (the throw expression is a `New_`) MUST NOT be flagged. This is wrapping, not rethrowing.
- **FR-003**: All other dummy patterns remain flagged: empty body, single `return`, single `throw $variable`, single `throw $methodCallResult`, etc.
- **FR-004**: Diagnostic identifier `smells.noDummyCatches` MUST be preserved.

## Success Criteria

- **SC-001**: `vendor/bin/pest tests/Rules/NoDummyCatchesTest.php` passes with the four named scenarios.
- **SC-002**: `npm test` continues all-green.
- **SC-003**: Diagnostic identifier remains `smells.noDummyCatches`.

## Assumptions

- Multi-class fixtures need an `autoload-dev.classmap` entry.
- The `New_` check is structural: `$throwStmt->expr instanceof Throw_ && $throwStmt->expr->expr instanceof New_`. We do not inspect what the new exception class is — any wrapping is acceptable.
