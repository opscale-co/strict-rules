# Implementation Plan: 001-model-validation-rule

**Branch**: `001-model-validation-rule`
**Spec**: `.specify/specs/001-model-validation-rule/spec.md`
**Date**: 2026-05-01

## Summary

Replace `ModelValidationRule`'s legacy target (`Enigma\ValidatorTrait` from `theriddleofenigma/laravel-model-validation`) with the canonical Opscale trait `Opscale\Validations\Validatable` from [`opscale-co/validations`](https://github.com/opscale-co/validations). Tighten the trait match to require the exact FQCN, and walk the inheritance chain so children of an already-validated parent are not falsely flagged. Refresh the rule, the documentation, the fixture, and the test suite to cover four scenarios: positive, negative, false positive (must not fire), and false negative (must fire).

## Technical Context

- **Language**: PHP 8.2
- **Static analyser**: PHPStan + Larastan, level 8
- **Test runner**: Pest 3.8 over PHPStan's `RuleTestCase`
- **Memory ceiling**: 512M (set in `npm test` script)
- **Diagnostic identifier**: `ddd.aggregates.modelValidation` (preserved for baseline stability)
- **Inheritance traversal**: `PHPStan\Reflection\ClassReflection::getParents()` returns an ordered list of ancestor reflections; `getTraits(true)` returns traits transitively (incl. parents). We use `ClassReflection::getNativeReflection()->getTraitNames()` walked manually for clarity, or `getTraits()` per ancestor.

## Constitution Check

- ✅ Article I — false positives and false negatives equate to broken information flow; this feature closes both.
- ✅ Article VIII (SOLID) — the rule still has a single responsibility (verify the `Validatable` contract). No new responsibilities, no new dependencies.
- ✅ Article X.1 — PHPStan level 8 must continue to pass on `src/`.
- ✅ Article X.6 — there is a corresponding test file (`tests/Rules/ModelValidationTest.php`) for the rule.
- ✅ Article X.7 — every variant of the rule's behaviour is exercised by at least one named test scenario.

## Project Structure

```
src/Rules/DDD/Aggregates/
├── ModelValidationRule.php          ← MODIFIED — FQCN match + inheritance walk + new error msg
├── ParentChildTransactionRule.php   ← unchanged
└── documentation.md                  ← MODIFIED — replace Enigma references with Validatable

tests/fixtures/Models/
├── Product.php                       ← unchanged (positive case fixture)
├── ValidatedModel.php                ← MODIFIED — switch to Opscale\Validations\Validatable
├── ValidatedAggregateRoot.php        ← NEW — abstract parent that uses Validatable
├── ValidatedChild.php                ← NEW — concrete child of ValidatedAggregateRoot
├── PretendingValidatedModel.php      ← NEW — uses Pretenders\Validatable (impostor)
└── Pretenders/
    └── Validatable.php               ← NEW — homonymous impostor trait

tests/Rules/
└── ModelValidationTest.php           ← MODIFIED — 4 named scenarios

composer.json                         ← MODIFIED — replace require-dev entry
```

## Phase 0 — Research (already done)

- The trait FQCN is `Opscale\Validations\Validatable`, confirmed from upstream `composer.json` (`"Opscale\\Validations\\": "src/"`) and the source file name `Validatable.php`.
- PHPStan's NameResolver runs before rules see the AST, so `$trait->toString()` on a `TraitUse` returns the FQCN.
- `ClassReflection::getParents()` is the cleanest way to walk the inheritance chain; we read each ancestor's `getTraits()` (immediate trait list) and stop on first match.

## Phase 1 — Design

### Rule logic (after change)

```
shouldProcess($node, $scope):
  if (!parent::shouldProcess) return false       // class in \Models, not enum, etc.
  if (!isEloquentModel($node))   return false    // is/extends Model
  return true

validate($node):
  $reflection = getClassReflection($node)
  if reflection is null: return [error("Unknown")]

  // Walk: self + ancestors
  for each $current in [reflection, ...reflection.getParents()]:
    for each $traitName in $current.getNativeReflection().getTraitNames():
      if $traitName === 'Opscale\\Validations\\Validatable':
        return []   // compliant

  return [error("must use Validatable from opscale-co/validations")]
```

The error message becomes:
```
Model class "Opscale\Models\Product" must use the "Validatable" trait
from the "opscale-co/validations" package to declare its validation rules.
```

### Test scenarios (test method names)

| # | Method name | Fixture | Expected errors |
|---|---|---|---|
| 1 | `caso_positivo_modelo_sin_trait` | `Models/Product.php` | 1 — at line 10 |
| 2 | `caso_negativo_modelo_con_trait` | `Models/ValidatedModel.php` | 0 |
| 3 | `falso_positivo_hijo_de_padre_validado` | `Models/ValidatedAggregateRoot.php` + `Models/ValidatedChild.php` | 0 (child compliant via parent) |
| 4 | `falso_negativo_trait_homonimo` | `Models/PretendingValidatedModel.php` (+ `Models/Pretenders/Validatable.php` to make the file parseable) | 1 — at line of class declaration |

### Documentation update (`src/Rules/DDD/Aggregates/documentation.md`)

- Replace every reference to `theriddleofenigma/laravel-model-validation` with `opscale-co/validations`.
- Replace every reference to `ValidatorTrait` (and `Enigma\ValidatorTrait`) with `Validatable` (and `Opscale\Validations\Validatable`).
- Update the `ModelValidationRule` table row "Condition" cell accordingly.
- Update the inline code example so the `Order` aggregate `use Opscale\Validations\Validatable;` and `use Validatable;`.

### composer.json

- Remove `theriddleofenigma/laravel-model-validation` from `require-dev`.
- Add `opscale-co/validations` to `require-dev` so consumer fixtures can import the trait without IDE noise.

## Phase 2 — Tasks (see `tasks.md`)

## Risks & Mitigations

| Risk | Mitigation |
|---|---|
| Existing consumer baselines reference the old error message text | The diagnostic identifier (`ddd.aggregates.modelValidation`) is preserved. Baselines key on identifier + line, not message. |
| `getTraits()` per ancestor misses traits used inside other traits (trait composition) | Acceptable for v1: aggregate-root validation is declared on the class or its abstract parent, not via nested trait composition. We can extend later if a real case appears. |
| `opscale-co/validations` is not yet on Packagist | Fixtures do not need the package installed at runtime — they are AST-only. The composer entry is for future Composer-resolved imports; if Packagist install fails, drop the require-dev line. |
| FQCN matching is case-sensitive — a developer typing `opscale\validations\validatable` would not match | Acceptable: PSR-4 is case-sensitive, and PHPStan would already complain about the wrong-case FQCN before this rule runs. |

## Out of Scope

- Walking nested trait composition (a trait that uses another trait that uses `Validatable`).
- Verifying that the `Validatable` trait's contract (rules method, etc.) is implemented by the model — that is a separate rule, not this one.
- Updating other rules — each rule gets its own feature folder, per the user's directive.
