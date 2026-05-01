# Implementation Plan: 002-parent-child-transaction-rule

**Branch**: `002-parent-child-transaction-rule`
**Spec**: `.specify/specs/002-parent-child-transaction-rule/spec.md`
**Date**: 2026-05-01

## Summary

Tighten `ParentChildTransactionRule` so that an Eloquent `belongsTo` relationship is recognised exclusively by the method's **return type** (not by the textual presence of a `belongsTo()` call inside the body), and walk the inheritance chain so children of abstract parents are no longer ignored. Add four named test scenarios covering positive, negative, false positive, and false negative.

## Technical Context

- **Language**: PHP 8.2
- **Static analyser**: PHPStan + Larastan, level 5 (target level 8 — separate cleanup)
- **Test runner**: Pest 3.8 over PHPStan's `RuleTestCase`
- **Diagnostic identifier**: `ddd.aggregates.parentChildTransaction` (preserved)
- **Inheritance traversal**: `PHPStan\Reflection\ClassReflection::getParents()` returns each ancestor reflection. For each one we call the existing `BaseRule::getASTForClass()` to re-parse its source file and walk its methods.

## Constitution Check

- ✅ Article I — false positives erode the rule's perceived value; false negatives let inconsistencies through. Both are equivalent to broken information flow.
- ✅ Article VIII (SOLID) — the rule retains a single responsibility (detect direct child saves). The change tightens its detection mechanism.
- ✅ Article X.1 — the change does not introduce any PHPStan level violation; it slightly simplifies the rule.
- ✅ Article X.6 — `tests/Rules/ParentChildTransactionTest.php` exists and is expanded.
- ✅ Article X.7 — every behaviour variant is exercised by a named scenario.

## Project Structure

```
src/Rules/DDD/Aggregates/
├── ParentChildTransactionRule.php   ← MODIFIED — return-type only + walk inheritance
├── ModelValidationRule.php           ← unchanged (touched in 001)
└── documentation.md                  ← MODIFIED — reflect new behaviour for ParentChildTransactionRule

tests/fixtures/Models/
├── Tenant.php                        ← NEW — aggregate root, no belongsTo
├── AbstractChildEntity.php           ← NEW — abstract parent with parent(): BelongsTo
├── InheritingChildEntity.php         ← NEW — concrete child, inherits the relationship
├── OrgPolicy.php                     ← NEW — body calls non-Eloquent $group->belongsTo()
└── Repositories/
    ├── ProductRepository.php          ← unchanged (positive case fixture)
    ├── TenantRepository.php           ← NEW — saves Tenant
    ├── InheritingChildRepository.php  ← NEW — saves InheritingChildEntity
    └── OrgPolicyRepository.php        ← NEW — saves OrgPolicy

tests/Rules/
└── ParentChildTransactionTest.php   ← MODIFIED — 4 named scenarios
```

## Phase 1 — Design

### Rule logic (after change)

```
shouldProcess($node, $scope):
  unchanged

validate($node):
  for each method in this Repository/Service file:
    for each save() MethodCall in the method:
      $callerType = parameter type hint matching the call's receiver var
      if $callerType is null:                continue
      if !isEloquentModel($callerType):       continue
      if !modelHasParentInChain($callerType): continue
      emit error with identifier ddd.aggregates.parentChildTransaction

modelHasParentInChain($className):
  $reflection = reflectionProvider->getClass($className)  # or null
  if !$reflection: return false
  $chain = [$reflection, ...$reflection.getParents()]
  for each $current in $chain:
    $classNode = getASTForClass($current.getName())
    for each method on $classNode:
      if isBelongsToReturn($method): return true
  return false

isBelongsToReturn($classMethod):
  if $classMethod.returnType is null: return false
  $name = $classMethod.returnType.toString()
  return $name in [
    'BelongsTo',
    'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
    'MorphTo',
    'Illuminate\\Database\\Eloquent\\Relations\\MorphTo',
  ]
```

The body-scan branch is removed entirely.

### Test scenarios (test method names)

| # | Method name | Files analysed | Expected errors |
|---|---|---|---|
| 1 | `caso_positivo_save_directo_en_hijo` | `Models/Repositories/ProductRepository.php` | 1 — line 12 |
| 2 | `caso_negativo_save_en_aggregate_root` | `Models/Repositories/TenantRepository.php` + `Models/Tenant.php` | 0 |
| 3 | `falso_positivo_belongsTo_helper_no_relacional` | `Models/Repositories/OrgPolicyRepository.php` + `Models/OrgPolicy.php` | 0 |
| 4 | `falso_negativo_belongsTo_heredado` | `Models/Repositories/InheritingChildRepository.php` + `Models/InheritingChildEntity.php` + `Models/AbstractChildEntity.php` | 1 |

### Documentation update (`src/Rules/DDD/Aggregates/documentation.md`)

The `ParentChildTransactionRule` table row is updated:

| Property | Value |
|---|---|
| Identifier | `ddd.aggregates.parentChildTransaction` |
| Scope | Method-level inside `\Models\Repositories` and `\Services` |
| Condition | Disallow `save()` on a parameter whose type (or any ancestor) declares a method with a `BelongsTo` or `MorphTo` return type. Recognition is **return-type only** — the textual presence of a `belongsTo()` call in a method body is no longer a signal. |

## Phase 2 — Tasks (see `tasks.md`)

## Risks & Mitigations

| Risk | Mitigation |
|---|---|
| Consumer relationship methods without typed returns become invisible to the rule | Documented as expected. Modern Laravel + strict-rules expects typed returns; untyped methods are themselves a smell. |
| `getASTForClass()` returns null when an ancestor's source file is not on disk (e.g. vendor classes inside phars) | Acceptable — only domain classes under `\Models` are walked in practice; vendor classes do not declare aggregate-root child relationships. |
| Walking a deep inheritance chain repeatedly re-parses files | Acceptable for a static-analysis rule; PHPStan caches at the file level. |

## Out of Scope

- Detecting `save()` on local variables or chained expressions — the existing rule only resolves parameter types; this feature does not change that.
- Other Eloquent relations (`hasOne`, `hasMany`, `BelongsToMany`, `HasManyThrough`) — they point downward from the aggregate root and are not "parent" relations.
