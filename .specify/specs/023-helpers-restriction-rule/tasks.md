# Tasks: 023-helpers-restriction-rule

## T001 — Add fixture: `tests/fixtures/Classes/MultiClassWithHelpers.php`
File with two classes: FirstCleanClass (DI only) + SecondHelperClass (uses `cache()->get('key')`). Add to autoload-dev.classmap.

## T002 — Modify `src/Rules/Smells/HelpersRestrictionRule.php`
Walk classlikes (Class_, Trait_, Enum_). Detection logic preserved.

## T003 — Rewrite `tests/Rules/HelpersRestrictionTest.php`
Four canonical scenarios.

## T004 — Verify
`composer dump-autoload`, `npm test` green. Commit `feat(rules)!:` (multi-class introduces new positives in consumer projects).
