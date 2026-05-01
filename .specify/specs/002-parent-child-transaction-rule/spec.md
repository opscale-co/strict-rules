# Feature Specification: ParentChildTransactionRule — return-type-only detection with inheritance walk

**Feature Branch**: `002-parent-child-transaction-rule`
**Created**: 2026-05-01
**Status**: Draft
**Input**: User description: "actualizar todas las reglas una por una; cada test debe incluir caso positivo, caso negativo, falso negativo y falso positivo; cada cambio en cada regla debe ser un nuevo feature"

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Block direct save() on child entities (Priority: P1)

As an architect, I want PHPStan to fail when a Repository or Service directly persists a child entity (a model with a `belongsTo` relationship), so that consistency invariants enforced by the aggregate root are not bypassed.

**Why this priority**: This is the rule's core purpose. Without it, child entities can mutate independently of their aggregate root, breaking domain invariants. P1.

**Independent Test**: Run `vendor/bin/pest tests/Rules/ParentChildTransactionTest.php`. The "true positive" and "true negative" scenarios verify the rule fires on a save() of a child and stays silent on a save() of an aggregate root.

**Acceptance Scenarios**:

1. **Caso positivo (true positive)** — **Given** a Repository method that calls `$product->save()` where `$product` is typed as `Product` and `Product` declares `public function user(): BelongsTo`, **When** PHPStan analyses the file, **Then** the rule reports exactly one error at the `save()` call line stating that the model has a parent relationship and must be saved through its aggregate.

2. **Caso negativo (true negative)** — **Given** a Repository method that calls `$tenant->save()` where `$tenant` is typed as `Tenant` and `Tenant` declares no `BelongsTo` relationship anywhere in its inheritance chain, **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 2 — Do not flag models that merely call `belongsTo()` somewhere (Priority: P2)

As a developer, I want the rule to recognise an Eloquent `belongsTo` relationship by the method's return type, not by the textual presence of a `belongsTo()` call inside the method body. Custom helper methods (e.g. permission checks that happen to call `$group->belongsTo($user)` on a non-Eloquent object) must not be confused with relationship declarations.

**Why this priority**: This is the **false-positive** scenario. Today the rule scans the body of every method on the model and flags any internal call to a method named `belongsTo`, regardless of receiver. This produces noise in domain code that uses `belongsTo` as a verb (auth, ACL, ownership). Closing it removes false alarms that erode trust in the rule.

**Independent Test**: A fixture model whose method body contains `$group->belongsTo($user)` (a non-Eloquent call) but declares no method with a `BelongsTo` return type, and a Repository that saves it, must produce zero errors.

**Acceptance Scenarios**:

1. **Falso positivo a evitar** — **Given** an Eloquent model `Opscale\Models\OrgPolicy` whose `isAuthorizedFor()` method body contains `$group->belongsTo($actor)` (a custom call on `$group`, not an Eloquent relationship), **And** `OrgPolicy` declares no method with a `BelongsTo` return type, **And** a Repository method calls `$policy->save()` on a parameter typed as `OrgPolicy`, **When** PHPStan analyses both files, **Then** the rule reports zero errors.

---

### User Story 3 — Detect inherited `belongsTo` relationships (Priority: P2)

As an architect, I want a child class that inherits a `belongsTo` relationship from an abstract parent to be treated as a child entity by the rule — even when the concrete subclass does not redeclare the relationship method.

**Why this priority**: This is the **false-negative** scenario. The current implementation parses only the concrete class's source, so an inherited relationship is invisible to the rule. Architects who share a base entity declaration (e.g. `AbstractChildEntity { public function parent(): BelongsTo {...} }`) get no protection — direct saves on subclasses pass silently. Closing it makes the rule consistent with how Eloquent inheritance actually works.

**Independent Test**: A fixture pair (`AbstractChildEntity` abstract parent with `parent(): BelongsTo` + `InheritingChildEntity extends AbstractChildEntity`) plus a Repository that saves an `InheritingChildEntity` parameter must produce one error.

**Acceptance Scenarios**:

1. **Falso negativo a evitar** — **Given** an abstract parent `AbstractChildEntity extends Model` that declares `public function parent(): BelongsTo`, **And** a concrete child `InheritingChildEntity extends AbstractChildEntity` that declares no relationship methods, **And** a Repository method calls `$child->save()` on a parameter typed as `InheritingChildEntity`, **When** PHPStan analyses all three files, **Then** the rule reports exactly one error on the `save()` line.

---

### Edge Cases

- A method whose return type is `MorphTo` (which extends `BelongsTo`) MUST also count as a parent relationship — `MorphTo` is semantically a parent.
- `$this->save()` inside a Repository trait that is mixed into a Model is not flagged today (`$this` does not match any parameter); this remains correct after the change because `$this` is not the concern of this rule.
- A method body that calls `$this->belongsTo(SomeModel::class)` but does NOT declare a return type is the canonical Laravel pre-7.x style. After this change, such methods are no longer detected. Consumer projects using untyped relationship methods must add `: BelongsTo` return types — this is consistent with the rest of strict-rules' typed-by-default expectations.
- A Repository method that calls `$model->save()` on a local variable (e.g. `$m = Product::find($id); $m->save();`) remains undetected — out of scope for this feature; tracked separately.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The rule MUST flag every `save()` call inside `\Models\Repositories` or `\Services` namespaces when the receiver's type (resolved from the enclosing method's parameter type hints) is an Eloquent model whose own class OR any ancestor declares at least one method whose return type is `BelongsTo` or `MorphTo`.
- **FR-002**: The rule MUST NOT use the textual presence of a `belongsTo()` method call inside a model's method body as a signal of a relationship. Only the declared return type counts.
- **FR-003**: The rule MUST resolve relationship presence by walking the class's inheritance chain via `ClassReflection::getParents()` and inspecting each ancestor's AST (using the existing `getASTForClass()` helper).
- **FR-004**: The rule MUST keep its diagnostic identifier `ddd.aggregates.parentChildTransaction` so existing baselines do not break.
- **FR-005**: The rule MUST keep the existing error message text format (`Direct save() on model "X" is not allowed. Models with parent relationships (belongsTo) should only be saved through their parent aggregates.`).

### Key Entities

- **ParentChildTransactionRule** — `src/Rules/DDD/Aggregates/ParentChildTransactionRule.php`. Extends `DomainRule`. Inspects every `save()` call inside `\Models\Repositories` and `\Services`.
- **Product** + **ProductRepository** fixtures — existing. The "positive" case.
- **Tenant** + **TenantRepository** fixtures (new) — model without any `BelongsTo`. The "negative" case.
- **OrgPolicy** + **OrgPolicyRepository** fixtures (new) — model whose method body calls a non-Eloquent `belongsTo`. The "false positive to avoid" case.
- **AbstractChildEntity** + **InheritingChildEntity** + **InheritingChildRepository** fixtures (new) — inheritance pair. The "false negative to avoid" case.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: `vendor/bin/pest tests/Rules/ParentChildTransactionTest.php` passes with at least four named scenarios: `caso_positivo_save_directo_en_hijo`, `caso_negativo_save_en_aggregate_root`, `falso_positivo_belongsTo_helper_no_relacional`, `falso_negativo_belongsTo_heredado`.
- **SC-002**: `npm test` continues to be all-green after the rule change — no regression on the 22 other rule tests, including the recently-added `001-model-validation-rule` scenarios.
- **SC-003**: `MorphTo` return types are accepted as parent relationships.
- **SC-004**: The PHPStan diagnostic identifier remains `ddd.aggregates.parentChildTransaction`.

## Assumptions

- Modern Laravel idiomatic style (PHP 8.2+) declares relationship return types on Eloquent models. This is consistent with strict-rules' typed-by-default posture; an untyped relationship method is itself a code smell that other rules may catch.
- `ClassReflection::getParents()` returns the inheritance chain in order from immediate parent to root. Each parent has a `getFileName()` we can pass to `getASTForClass()`.
- `MorphTo` is the only standard Laravel relation that semantically *is* a parent and extends `BelongsTo`. Other relations (`HasOne`, `HasMany`, `BelongsToMany`, etc.) point downward from the aggregate root and are out of scope.
