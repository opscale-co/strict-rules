# Tasks: 017-conditional-override-rule

## T001 — Add fixture: `tests/fixtures/Models/MagicMethodsModel.php`
Class with `__construct`, `__toString`, `__invoke` (none marked `final`). Used by falso_positivo.

## T002 — Add fixture: `tests/fixtures/Models/MultiClassConditionalOverride.php` [P]
File with two classes: FirstFinalClass (all methods final) + SecondNonFinalClass (one public unfinaled method). Add to autoload-dev.classmap.

## T003 — Modify `src/Rules/SOLID/OCP/ConditionalOverrideRule.php`
Walk classlikes (Class_, Trait_, Enum_). Skip magic methods (`__` prefix).

## T004 — Rewrite `tests/Rules/ConditionalOverrideTest.php`
Four canonical scenarios.

## T005 — Update `src/Rules/SOLID/OCP/documentation.md`
Refresh with magic-method skip + multi-class walking + identifier.

## T006 — Verify
`composer dump-autoload`, `npm test` green. Commit `feat(rules):` (additive: previously-flagged magic methods now pass; multi-class walking adds new positive cases — could be considered breaking by the multi-class addition, so use `feat(rules)!`).
