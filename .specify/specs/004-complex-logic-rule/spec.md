# Feature Specification: ComplexLogicRule — operation-based detection with `\Services\*` exemption

**Feature Branch**: `004-complex-logic-rule`
**Created**: 2026-05-01
**Status**: Draft
**Input**: User description: "operaciones complejas (que requieren mas de 2 modelos) solo se pueden hacer dentro de opscale-co/actions dentro de root/Services. Hoy la regla cuenta referencias a modelos pero eso lanza falsos positivos: un Nova resource o cualquier clase puede usar varios modelos solo por type-hint o `::class` y no hacer operaciones de consulta."

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Block multi-entity operations outside `\Services\*` (Priority: P1)

As an architect, I want PHPStan to fail when a class outside `\Services\*` actually executes operations on more than two distinct Eloquent models, so that complex coordination across entities lives only inside Opscale Actions (or Domain Services), per Constitution Article V.

**Why this priority**: This is the rule's core purpose. Without it, controllers, jobs, observers, and ad-hoc helpers can quietly orchestrate cross-entity state changes that should live inside an Opscale Action. P1.

**Independent Test**: Run `vendor/bin/pest tests/Rules/ComplexLogicTest.php`. The "true positive" and "true negative" scenarios verify the rule fires on a Job that operates on three models and stays silent on a Service that does the same.

**Acceptance Scenarios**:

1. **Caso positivo (true positive)** — **Given** a class `Opscale\Jobs\MultiModelJob` outside `\Services\*` whose `handle()` body contains a `StaticCall` on `User`, a `MethodCall` `->save()` on a `Product` parameter, and a `New_` of `Tenant`, **When** PHPStan analyses the file, **Then** the rule reports exactly one error stating the class operates on three distinct Eloquent models and that complex logic must live in `\Services\*`.

2. **Caso negativo (true negative)** — **Given** a class `Opscale\Services\CrossEntityService` under `\Services\*` whose `orchestrate()` body executes static-call queries on four distinct Eloquent models, **When** PHPStan analyses the file, **Then** the rule reports zero errors, because `\Services\*` is the legitimate home of multi-entity coordination.

---

### User Story 2 — Do not flag mere references (type hints, `::class`) (Priority: P2)

As a developer of Form Requests, Nova Resources, DTOs, or contract classes that legitimately reference many models for shape declaration purposes (type hints in method signatures, `Model::class` in constants, return types, properties), I want the rule to ignore these references because they are not operations — they neither query, instantiate, nor mutate any entity.

**Why this priority**: This is the **false-positive** scenario. The current implementation counts `use` imports, which conflates references with operations. Form Requests with multiple typed dependencies, Nova Resources whose fields point at related models via `::class`, and DTOs that aggregate several model types all get flagged spuriously today. Closing this restores the rule's signal.

**Independent Test**: A fixture class outside `\Services\*` with type hints to three Models, three `Model::class` constant references, and zero `StaticCall`/`New_`/`->save()` invocations on those models must produce zero errors.

**Acceptance Scenarios**:

1. **Falso positivo a evitar** — **Given** a class `Opscale\Http\MultiModelFormRequest` outside `\Services\*` with three parameter type hints (`User $u, Product $p, Tenant $t`) and a constant array `[User::class, Product::class, Tenant::class]`, **And** zero static calls or `new` expressions or `->save()` calls on any model, **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 3 — Detect FQCN inline calls without imports (Priority: P2)

As an architect, I want a class that uses fully-qualified Eloquent model names directly (`\Opscale\Models\User::find(1)`) without any `use` import to still be caught when it operates on more than two distinct models, so the rule cannot be sidestepped by inlining FQCNs.

**Why this priority**: This is the **false-negative** scenario. The current rule counts `use` statements, so a class that avoids imports and writes FQCNs directly is invisible to it. PHPStan's NameResolver normalises both forms to the same FQCN before this rule runs, so the new operation-based metric catches both naturally. Closing this matches the rule's intent regardless of import style.

**Independent Test**: A fixture class outside `\Services\*` whose body contains three `StaticCall` expressions written with full FQCNs and no `use` imports must produce one error.

**Acceptance Scenarios**:

1. **Falso negativo a evitar** — **Given** a class `Opscale\Jobs\FQCNDirectJob` outside `\Services\*` whose `handle()` body contains `\Opscale\Models\User::find(1)`, `\Opscale\Models\Product::find(1)`, and `\Opscale\Models\Tenant::find(1)`, **When** PHPStan analyses the file, **Then** the rule reports exactly one error.

---

### Edge Cases

- A class outside `\Services\*` operating on **exactly two** distinct Eloquent models is **not** flagged. The threshold is "more than two" — coordinating two entities (e.g., updating a User and creating a relationship row) is acceptable in any class.
- `Builder` operations (`$query->where(...)` where `$query` is `Illuminate\Database\Eloquent\Builder`) are not Model operations and are not counted. This means Nova `indexQuery($request, $query)` overrides remain unflagged.
- `instanceof Model` is not an operation — it is a type guard. Not counted.
- `Model::class` is a constant reference, not an operation. Not counted.
- A `MethodCall` `->save()` whose receiver is a property (`$this->user->save()`) is not counted under v1; only typed parameters are inspected. Property type inference is out of scope.
- An anonymous class is skipped (handled by `BaseRule::shouldProcess`).

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The rule MUST extend `BaseRule` directly. It MUST NOT use `DomainRule`'s namespace gating (which restricts to `\Models`).
- **FR-002**: The rule MUST skip any class whose namespace matches `\Services\*` (including `\Services\Actions\*` and any deeper subnamespace).
- **FR-003**: The rule MUST count distinct Eloquent model FQCNs that appear as the target of one of the following AST nodes:
  - `StaticCall` whose `class` resolves to an Eloquent Model (`User::find()`).
  - `New_` whose `class` resolves to an Eloquent Model (`new User(...)`).
  - `MethodCall` whose `name` is `save` AND whose receiver `var` is a `Variable` matching, by name, a parameter of the enclosing `ClassMethod` whose declared type resolves to an Eloquent Model.
- **FR-004**: The rule MUST NOT count `ClassConstFetch`, parameter / property / return type declarations, `Use_` imports, or `instanceof` expressions.
- **FR-005**: The rule MUST emit at most one error per class. The error names the class, the count, and the distinct model FQCNs operated upon (sorted alphabetically for deterministic test assertions).
- **FR-006**: The threshold is `> 2`. A class that operates on 0, 1, or 2 distinct Eloquent models MUST NOT be flagged.
- **FR-007**: The diagnostic identifier MUST remain `ddd.domainServices.complexLogic`.

### Key Entities

- **ComplexLogicRule** — `src/Rules/DDD/DomainServices/ComplexLogicRule.php`. Extends `BaseRule`. Inspects every class outside `\Services\*` for multi-entity operations.
- **MultiModelJob** fixture (new) — non-Service class with three operations on distinct models. Positive case.
- **CrossEntityService** fixture (new) — Service class with four operations. Negative case (exempt by namespace).
- **MultiModelFormRequest** fixture (new) — non-Service class with type hints and class constants only. False-positive case.
- **FQCNDirectJob** fixture (new) — non-Service class with FQCN-inline static calls. False-negative case.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: `vendor/bin/pest tests/Rules/ComplexLogicTest.php` passes with at least four named scenarios: `caso_positivo_tres_modelos_en_operaciones`, `caso_negativo_service_con_muchos_modelos`, `falso_positivo_solo_type_hints_y_class_refs`, `falso_negativo_fqcn_en_linea_sin_imports`.
- **SC-002**: `npm test` continues to be all-green after the rule change.
- **SC-003**: The new error message contains both the count of distinct models and at least one of the offending model FQCNs, so developers can trace the violation back to the offending lines.
- **SC-004**: The PHPStan diagnostic identifier remains `ddd.domainServices.complexLogic`.

## Assumptions

- PHPStan's NameResolver runs before the rule sees the AST, so `User::find()` (with `use Opscale\Models\User;`) and `\Opscale\Models\User::find()` both produce a `StaticCall` whose `class` is the FQCN `Opscale\Models\User`.
- `ReflectionProvider::isSubclassOf(Model::class)` reliably identifies Eloquent models. The cost of one reflection lookup per static call is acceptable for static analysis.
- `MethodCall` on properties is rare in non-Service code and not worth the complexity for v1. Tracked separately if a real need emerges.
- Persistence methods other than `save()` (`delete`, `update`, `restore`, `forceDelete`, `fill`) are out of scope for v1. Only `save()` is recognised, per the user's explicit requirement.
