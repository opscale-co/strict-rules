# Feature Specification: BaseNamespaceRule — flat `\Models` enforcement + multi-class walking

**Feature Branch**: `007-base-namespace-rule`
**Created**: 2026-05-01
**Status**: Draft
**Input**: User description: "los modelos deben estar en la raiz de Models, sin subfolders"

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Force domain entities into a `\Models` segment, flat (Priority: P1)

As an architect, I want PHPStan to fail when a class extending `Illuminate\Database\Eloquent\Model` lives in a namespace that does not END with the `Models` segment. Models must sit directly under `\Models` — no subfolders, no aggregate-grouping subnamespaces. The aggregate's child entities live alongside the root, not inside it.

**Why this priority**: Constitution Article IV — "Subdomains as Packages". The user's clarification refines this: models must be at the **root** of the `Models` namespace, flat. P1.

**Acceptance Scenarios**:

1. **Caso positivo (true positive)** — **Given** an Eloquent model `Opscale\Domain\User` whose namespace `Opscale\Domain` does not end with `\Models`, **When** PHPStan analyses the file, **Then** the rule reports exactly one error.

2. **Caso negativo (true negative)** — **Given** an Eloquent model `Opscale\Models\ValidUlidUser` whose namespace `Opscale\Models` ends with `\Models`, **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 2 — Do not flag non-Eloquent classes (Priority: P2)

As a developer, I want the rule to leave non-Eloquent classes (regular helpers, value objects, DTOs) alone regardless of which namespace they live in. The rule's contract is purely about Eloquent models — non-Eloquent classes must never be flagged for "wrong namespace".

**Why this priority**: This is the **false-positive** scenario. After the implementation broadens to walk every class in a file (see User Story 3), an over-eager check could mis-flag non-Eloquent classes that happen to share a file or namespace with a Model. The rule must remain narrow: only Eloquent subclasses are subject to namespace enforcement.

**Acceptance Scenarios**:

1. **Falso positivo a evitar** — **Given** a file under `\Opscale\Domain` whose only declaration is `class JustAHelper {}` (no inheritance, no `extends Model`), **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 3 — Walk every class declaration in a file (Priority: P2)

As an architect, I want the rule to inspect every class defined in a file, not just the first one. PHP allows multiple class declarations per file; today's rule reads the first via `getClassReflection`, sees a non-Model, and exits — any later Eloquent class slips through undetected.

**Why this priority**: This is the **false-negative** scenario. The rule's `shouldProcess` short-circuits on the first class. A second Eloquent class declared in the same file (under the same namespace) is invisible to today's rule. Closing this restores the rule's coverage on multi-class files.

**Acceptance Scenarios**:

1. **Falso negativo a evitar** — **Given** a file at `\Opscale\Domain` whose first declaration is `class FirstHelper {}` (not an Eloquent Model) and whose second declaration is `class SecondModel extends Model {}` (Eloquent Model in the wrong namespace), **When** PHPStan analyses the file, **Then** the rule reports exactly one error pointed at the second class declaration line.

---

### Edge Cases

- A class with no namespace declaration (global namespace) — flagged because the empty namespace cannot end with `\Models`.
- Anonymous classes — skipped (no `namespacedName`).
- Interfaces, traits, and enums — skipped (the rule walks `Class_` nodes only and `isEloquentModelClassName` returns false for non-classes).
- A class inside a nested subnamespace under Models (`\Opscale\Models\Aggregate\Order`) — flagged. Per the user's "no subfolders" clarification, nested layouts are not allowed.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The rule MUST extend `BaseRule` directly. It MUST NOT inherit `DomainRule`'s `\Models\*` gate.
- **FR-002**: `shouldProcess` MUST return true for any `FileNode`. The per-class filtering happens inside `validate`.
- **FR-003**: `validate` MUST iterate over every `Class_` node returned by `getClassNodes($node)`. For each class:
  - Skip if the class has no resolved `namespacedName`.
  - Skip if the class FQCN is not an Eloquent Model (subclass of `Illuminate\Database\Eloquent\Model`).
  - Compute the file's namespace and check `str_ends_with($namespace, '\\Models')`.
  - Emit one error per non-conforming Eloquent class, using the class's own line number.
- **FR-004**: The error message MUST name the offending FQCN and explicitly state that "the class must live directly under a `\Models` namespace, with no subfolders".
- **FR-005**: Diagnostic identifier `ddd.subdomains.baseNamespace` MUST be preserved.

### Key Entities

- **BaseNamespaceRule** — `src/Rules/DDD/Subdomains/BaseNamespaceRule.php`. Now extends `BaseRule`.
- **Domain/User** fixture — existing positive case.
- **Models/ValidUlidUser** fixture — existing direct-namespace negative.
- **Domain/JustAHelper** fixture (new) — false-positive guard (non-Eloquent class in wrong namespace).
- **Domain/MultiClassFile** fixture (new) — false-negative case (multi-class file).

## Success Criteria *(mandatory)*

- **SC-001**: `vendor/bin/pest tests/Rules/BaseNamespaceTest.php` passes with the four named scenarios.
- **SC-002**: `npm test` continues all-green.
- **SC-003**: The PHPStan diagnostic identifier remains `ddd.subdomains.baseNamespace`.
- **SC-004**: The error message wording reflects the "flat under `\Models`" rule.

## Assumptions

- Per the user's clarification, the rule treats nested subnamespaces under `\Models` (e.g., `\Models\Aggregate\Order`) as violations. The check stays as `str_ends_with($namespace, '\\Models')` — semantically: the namespace must literally end with the `\Models` segment.
- PHPStan's NameResolver runs before the rule, so `$classNode->namespacedName` is the FQCN for every named class declaration.
- A file may contain multiple `Class_` declarations under the same namespace (PHP supports this even though it is uncommon).
- The line reported by the rule moves from the namespace-declaration line (today) to the class-declaration line (after). This is more precise — each Model gets its own pointer — and the existing test will be updated accordingly.
