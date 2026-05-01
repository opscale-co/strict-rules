# Tasks: 018-parent-call-rule

## T001 — Add fixture: `tests/fixtures/Models/ProperOverrider.php`
Class extending `AbstractParentModel`. Implements abstract `getName()` and overrides concrete `getDescription()` calling `parent::getDescription()`. Used by caso_negativo.

## T002 — Add fixture: `tests/fixtures/Models/AbstractImplementer.php` [P]
Class extending `AbstractParentModel`. Only implements abstract `getName()`. Used by falso_positivo.

## T003 — Add fixture: `tests/fixtures/Models/MultiClassParentCall.php` [P]
Two classes: FirstProperOverrider (calls parent::) + SecondImproperOverrider (does not). Add to autoload-dev.classmap.

## T004 — Modify `src/Rules/SOLID/LSP/ParentCallRule.php`
Walk classlikes (Class_, Trait_, Enum_). Move parent-class check inside the loop. Drop the single-class `getParentNode` gate from `shouldProcess`.

## T005 — Rewrite `tests/Rules/ParentCallTest.php`
Four canonical scenarios.

## T006 — Update `src/Rules/SOLID/LSP/documentation.md`
Refresh `ParentCallRule` block with multi-class walking + identifier.

## T007 — Verify
`composer dump-autoload`, `npm test` green. Commit `feat(rules)!:` (multi-class walking adds new positive cases for previously-missed classes).
