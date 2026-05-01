# Feature Specification: EloquentRestrictionRule — broad scope with `\Models\Repositories\*` + `\Services\*` exemption

**Feature Branch**: `006-eloquent-restriction-rule`
**Created**: 2026-05-01
**Status**: Draft

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Eloquent calls only inside Repositories or Services (Priority: P1)

As an architect, I want PHPStan to fail when any class outside `\Models\Repositories\*` or `\Services\*` invokes Eloquent query / persistence / relationship methods, so that infrastructure concerns stay isolated and the domain model remains testable.

**Why this priority**: Constitution Article IV — "All database queries live in Repository classes. No Eloquent query builder calls exist outside of Repositories." Article V allows Services to coordinate operations on multiple aggregates; this rule extends that allowance to `\Services\*` to mirror the user's clarification. P1.

**Acceptance Scenarios**:

1. **Caso positivo (true positive)** — **Given** the Eloquent model `Opscale\Models\Product` (NOT under `\Models\Repositories\*`) which contains `self::where(...)`, `$this->where(...)`, and `$this->belongsTo(...)` calls, **When** PHPStan analyses the file, **Then** the rule reports three errors at the call lines, each naming the offending method and the class.

2. **Caso negativo (true negative)** — **Given** a trait `Opscale\Models\Repositories\ProductRepository` that uses `$product->save()` and a class `Opscale\Services\CrossEntityService` that uses multiple static Eloquent calls, **When** PHPStan analyses both files, **Then** the rule reports zero errors — both namespaces are exempt.

---

### User Story 2 — Do not flag homonymous static calls in non-Eloquent classes (Priority: P2)

As a developer of utility / domain-helper classes that happen to define a method whose name matches an Eloquent method (e.g., `find`, `get`, `clone`), I want the rule to ignore `self::find()` style calls when the enclosing class is NOT an Eloquent model. The static call resolves to the class's own method, not to Eloquent's.

**Why this priority**: This is the **false-positive** scenario. The rule's previous scope (only `\Models\*`) made this impossible to hit, but now that the scope is broadened any non-Eloquent class with a homonymous method would otherwise be mis-flagged. The fix is structural: `self::`/`static::`/`parent::` calls only count as Eloquent when the enclosing class is an Eloquent model.

**Acceptance Scenarios**:

1. **Falso positivo a evitar** — **Given** a non-Eloquent class `Opscale\Domain\Locator` with a method `find()` and another method that calls `self::find($key)`, **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 3 — Detect Eloquent static calls anywhere outside Repositories / Services (Priority: P2)

As an architect, I want a Controller, Job, Listener, Nova class, or any other non-Repository / non-Service class that calls `User::find($id)` to be flagged. Today's rule only inspected classes under `\Models\*`, so these critical leaks went unreported.

**Why this priority**: This is the **false-negative** scenario. Eloquent calls in HTTP controllers and Jobs are exactly where the constitution forbids them, but the previous scope let them slip through. Closing this is the rule's main reason to exist.

**Acceptance Scenarios**:

1. **Falso negativo a evitar** — **Given** a class `Opscale\Http\UserController` (outside `\Models\*`, `\Models\Repositories\*`, and `\Services\*`) whose method calls `User::find($id)`, **When** PHPStan analyses the file, **Then** the rule reports exactly one error at the call line.

---

### Edge Cases

- A trait under `\Models\Repositories\*` is exempt (handled by the new shouldProcess).
- A class under `\Services\Actions\*` is exempt (`\Services\*` matches subnamespaces).
- A class under `\Models\*` (NOT under `\Models\Repositories\*`) is processed — Models themselves cannot query.
- `Use_` imports of an Eloquent model class are not operations; only static calls / `$this->method()` count.
- An anonymous class is skipped (`BaseRule::shouldProcess`).
- An Enum is skipped.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The rule MUST extend `BaseRule` directly. It MUST NOT use `DomainRule`'s `\Models\*` gate.
- **FR-002**: The rule MUST skip any class whose namespace matches `\Models\Repositories\*` or `\Services\*`.
- **FR-003**: The rule MUST flag a `StaticCall` whose `class` resolves to an Eloquent Model FQCN AND whose method name is in the curated Eloquent-method list, regardless of the enclosing class.
- **FR-004**: The rule MUST flag a `StaticCall` to `self`, `static`, or `parent` with an Eloquent method name only when the enclosing class is an Eloquent Model.
- **FR-005**: The rule MUST flag a `MethodCall` on `$this` with an Eloquent method name only when the enclosing class is an Eloquent Model.
- **FR-006**: The error message MUST mention both allowed namespaces (`\Models\Repositories\*` and `\Services\*`), name the offending method, and name the offending class.
- **FR-007**: Diagnostic identifier `ddd.repositories.eloquentRestriction` MUST be preserved.

### Key Entities

- **EloquentRestrictionRule** — `src/Rules/DDD/Repositories/EloquentRestrictionRule.php`. Now extends `BaseRule`.
- **Product** fixture — existing positive case (Model with self/$this Eloquent calls).
- **ProductRepository** fixture — existing repository-trait negative case.
- **CrossEntityService** fixture — Service negative case, reused from feature 004.
- **Locator** fixture (new) — non-Eloquent class with `self::find()` for the false-positive scenario.
- **UserController** fixture (new) — controller-style class outside Services for the false-negative scenario.

## Success Criteria *(mandatory)*

- **SC-001**: `vendor/bin/pest tests/Rules/EloquentRestrictionTest.php` passes with the four named scenarios.
- **SC-002**: `npm test` continues all-green.
- **SC-003**: The PHPStan diagnostic identifier remains `ddd.repositories.eloquentRestriction`.
- **SC-004**: The error message is updated to mention both `\Models\Repositories\*` and `\Services\*`.

## Assumptions

- PHPStan's NameResolver runs before the rule, so a `StaticCall` to `User::find(...)` (regardless of import style) yields `class` whose `toString()` is the FQCN.
- The curated Eloquent-method list (≈170 entries) covers query, persistence, relationship, and utility methods. Some have generic names (`is`, `clone`, `find`, `get`) — those are guarded by the "enclosing class must be a Model" rule when the receiver is `$this`/`self`.
- Relationship declaration methods (`belongsTo`, `hasOne`, etc.) remain in the method list. The constitution treats relationship declarations as repository concerns (consistent with the existing fixture pattern where `UserRepository` trait is mixed into the User model and declares its relationships).
