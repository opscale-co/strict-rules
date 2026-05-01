# Tasks: 022-no-dummy-catches-rule

## T001 — Add fixture: `tests/fixtures/Jobs/WrappingExceptionJob.php`
Job whose catch block contains a single `throw new RuntimeException(..., 0, $e);`. Used by falso_positivo.

## T002 — Add fixture: `tests/fixtures/Jobs/MultiClassDummyCatch.php` [P]
Two classes: FirstHandledJob (catch with substantive body) + SecondDummyJob (empty catch). Add to autoload-dev.classmap.

## T003 — Modify `src/Rules/Smells/NoDummyCatchesRule.php`
Walk classlikes. Add wrapping exemption: skip single-throw blocks whose throw expression is a `New_`.

## T004 — Rewrite `tests/Rules/NoDummyCatchesTest.php`
Four canonical scenarios.

## T005 — Verify
`composer dump-autoload`, `npm test` green. Commit `feat(rules):` (additive — wrapping no longer flagged) but with multi-class introducing new positives -> use `feat(rules)!:`.
