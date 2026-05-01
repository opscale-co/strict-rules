# Tasks: 009-enforce-cast-rule

## T001 — Add fixture: `tests/fixtures/Models/ValueObjects/AbstractCastableValueObject.php`
Abstract base class under `\Opscale\Models\ValueObjects` that implements `CastsAttributes`.

## T002 — Add fixture: `tests/fixtures/Models/ValueObjects/InheritingValueObject.php` [P]
Concrete class extending `AbstractCastableValueObject`, no inline `implements`.

## T003 — Add fixture: `tests/fixtures/Models/ValueObjects/MultiVOFile.php` [P]
Single file with two class declarations under `\Opscale\Models\ValueObjects`. The first class implements `CastsAttributes`; the second class does not. Add to `autoload-dev.classmap`.

## T004 — Rewrite `src/Rules/DDD/ValueObjects/EnforceCastRule.php`
Extend `BaseRule`. New `shouldProcess` (FileNode + namespace gate). New `validate` walks `getClassNodes`, skips abstract / unresolved, uses `ClassReflection::getInterfaces()` for the transitive check. Per-class line in the error.

## T005 — Rewrite `tests/Rules/EnforceCastTest.php`
Consolidate the 5 legacy tests into the four canonical scenarios.

## T006 — Update `src/Rules/DDD/ValueObjects/documentation.md`
Refresh the `EnforceCastRule` block: identifier, transitive interface detection, multi-class walking, abstract skip.

## T007 — Verify
`composer dump-autoload`, `npm test` green. Commit `feat(rules)!:` with `BREAKING CHANGE:` footer.
