# Tasks: 016-max-lines-rule

## T001 — Add fixture: `tests/fixtures/Models/SmallClassWithManyImports.php`
File with namespace + ~30 use statements + a 5-line class. File total > 25 (threshold), class body ≤ 25.

## T002 — Add fixture: `tests/fixtures/Models/MultiClassFatClasses.php` [P]
File with two classes under `Opscale\Models`, each with ~30 properties so each class body exceeds 25 lines. Add to `autoload-dev.classmap`.

## T003 — Modify `src/Rules/SOLID/SRP/MaxLinesRule.php`
Walk classlike nodes per file. Measure per-class lines. Default 500.

## T004 — Rewrite `tests/Rules/MaxLinesTest.php`
Four canonical scenarios (threshold 25 in test setup).

## T005 — Update `src/Rules/SOLID/SRP/documentation.md`
Refresh with class-level measurement, multi-class walking, identifier.

## T006 — Verify
`composer dump-autoload`, `npm test` green. Commit `feat(rules)!:` (the line count behavior changes for files with imports — consumer baselines pinned by line number need regeneration).
