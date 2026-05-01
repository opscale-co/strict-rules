# Feature Specification: NoStatementsLogicRule — match detection + closure exclusion

**Feature Branch**: `003-no-statements-logic-rule`
**Created**: 2026-05-01
**Status**: Draft
**Input**: User description: "actualizar todas las reglas una por una; cada test debe incluir caso positivo, caso negativo, falso negativo y falso positivo; cada cambio en cada regla debe ser un nuevo feature"

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Block control flow inside domain models (Priority: P1)

As an architect, I want PHPStan to fail when an Eloquent model under `\Models` contains imperative control-flow statements directly in its method bodies, so that domain models stay declarative and conditional logic is delegated to Actions or Domain Services.

**Why this priority**: Constitution Article IV states that Models contain only declarative property definitions and casts — `if` / loops / computations belong in Actions. P1.

**Independent Test**: Run `vendor/bin/pest tests/Rules/NoStatementsLogicTest.php`. The "true positive" and "true negative" scenarios verify the rule fires on a model with an `if` and stays silent on a fully declarative model.

**Acceptance Scenarios**:

1. **Caso positivo (true positive)** — **Given** an Eloquent model `Opscale\Models\User` whose `getEmail()` method body contains an `if` statement, **When** PHPStan analyses the file, **Then** the rule reports exactly one error at the `if` line.

2. **Caso negativo (true negative)** — **Given** an Eloquent model `Opscale\Models\ValidUlidUser` whose methods contain only declarative `casts()`, fillable arrays, and trait declarations, **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 2 — Allow control flow encapsulated inside closures (Priority: P2)

As a developer using Laravel's idiomatic accessor/mutator pattern (`Attribute::make(get: function (...) { ... })`), I expect the rule to skip the body of any `Closure` or `ArrowFunction` declared inside a model's method. The closure encapsulates its own scope; the model itself remains declarative because the conditional dispatch lives inside a callback handed to a framework helper.

**Why this priority**: This is the **false-positive** scenario. The current implementation uses `NodeFinder::findInstanceOf` which descends recursively, so any `if` inside a closure inside `Attribute::make(...)` is flagged as a model-level violation. That mis-classifies a Laravel idiom and creates noise that erodes trust in the rule.

**Independent Test**: A fixture model whose only `if` lives inside a closure passed to `Attribute::make(get: function (...) { if (...) { ... } })` must produce zero errors.

**Acceptance Scenarios**:

1. **Falso positivo a evitar** — **Given** an Eloquent model `Opscale\Models\DeclarativeAccessorModel` whose `name()` method returns `Attribute::make(get: function (...) { if (...) { ... } })`, **And** the model has no other control flow outside the closure, **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 3 — Detect `match` expressions (Priority: P2)

As an architect, I want a `match` expression in a domain model's method body to be reported as a control-flow violation, on the same footing as `if`, `switch`, and the loop constructs. `match` is conditional dispatch — it belongs in Actions, not in Models.

**Why this priority**: This is the **false-negative** scenario. The current rule predates PHP 8.0 `match` and only detects `if`, `switch`, `foreach`, `for`, `while`, `do`. A model author can sidestep the rule by using `match` to do exactly what `switch` would have done. Closing this gap restores the rule's intent.

**Independent Test**: A fixture model with a method whose body returns a `match` expression must produce one error per match.

**Acceptance Scenarios**:

1. **Falso negativo a evitar** — **Given** an Eloquent model `Opscale\Models\MatchUsingModel` whose `status()` method body returns a `match($this->state) { ... }`, **When** PHPStan analyses the file, **Then** the rule reports exactly one error at the `match` line stating that the method contains a `match` statement which is not allowed in domain model classes.

---

### Edge Cases

- The `__construct` method is exempt — the existing skip remains.
- `try`/`catch` and ternary expressions are deliberately out of scope for this feature; tracked separately.
- Closures inside other closures are still skipped (the visitor returns `DONT_TRAVERSE_CHILDREN` on every closure entry).
- An arrow function (`fn () => ...`) is by definition a single expression, but if the expression is itself a `match`, the model-level check still skips it because the arrow function is a closure-like node.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The rule MUST flag every occurrence of `if`, `switch`, `match`, `foreach`, `for`, `while`, `do-while` in the body of any non-`__construct` method of an Eloquent model under `\Models`.
- **FR-002**: The rule MUST NOT descend into the body of `Closure` or `ArrowFunction` nodes when looking for control flow. Control flow inside such closures is encapsulated and is not the model's concern.
- **FR-003**: The error message format remains `Method "<FQCN>::<method>" contains a "<statement>" statement which is not allowed in domain model classes.`, with `<statement>` taking the values `if`, `switch`, `match`, `foreach`, `for`, `while`, `dowhile`.
- **FR-004**: The diagnostic identifier MUST remain `ddd.domain.noStatementsLogic`.

### Key Entities

- **NoStatementsLogicRule** — `src/Rules/DDD/Domain/NoStatementsLogicRule.php`. Extends `DomainRule`. Inspects every method on an Eloquent model.
- **User** fixture — existing. The "positive" case (already has an `if` in `getEmail()`).
- **ValidUlidUser** fixture — existing. The "negative" case.
- **DeclarativeAccessorModel** fixture (new) — uses `Attribute::make(get: function (...) { if ... })`. The "false positive to avoid" case.
- **MatchUsingModel** fixture (new) — has a `match` expression in a method body. The "false negative to avoid" case.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: `vendor/bin/pest tests/Rules/NoStatementsLogicTest.php` passes with at least four named scenarios: `caso_positivo_if_directo`, `caso_negativo_modelo_declarativo`, `falso_positivo_if_dentro_de_closure`, `falso_negativo_match_en_metodo`.
- **SC-002**: `npm test` continues to be all-green after the rule change — no regression on the 23 other rule tests.
- **SC-003**: The rule recognises `match` as a control-flow statement and emits the exact statement-name `match` in the error message.
- **SC-004**: The PHPStan diagnostic identifier remains `ddd.domain.noStatementsLogic`.

## Assumptions

- The standard Laravel accessor/mutator pattern in modern apps is `Attribute::make(get: function (...) { ... })`. Skipping closures inside this pattern is consistent with the constitution's allowance of "declarative cast configurations".
- `nikic/php-parser` exposes `Closure` and `ArrowFunction` AST nodes that can be matched in a `NodeVisitor` via `instanceof`.
- The rule is run within PHPStan's standard pipeline, which guarantees parser names are resolved and class structures are stable.
