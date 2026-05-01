# Tasks: 014-transformation-layer-rule

## T001 — Add fixture: `tests/fixtures/Services/ServiceUsingHelpers.php`
Service under `Opscale\Services` importing `Illuminate\Support\Arr`, `Illuminate\Support\Collection`, `Illuminate\Support\Str`, `Illuminate\Support\Number`, `Log` facade, and a project Model.

## T002 — Add fixture: `tests/fixtures/Services/DbAccessService.php` [P]
Service under `Opscale\Services` importing `Illuminate\Support\Facades\DB`. Used by falso_negativo regression.

## T003 — Modify `src/Rules/CLEAN/Transformation/TransformationLayerRule.php`
Add the four specific Support classes to allowedFrameworkImports. Add Log to allowedFacades.

## T004 — Rewrite `tests/Rules/TransformationLayerTest.php`
Four canonical scenarios.

## T005 — Update `src/Rules/CLEAN/Transformation/documentation.md`
Add Support helpers and Log facade.

## T006 — Verify
`npm test` green. Commit `feat(rules):` (no `!` — additive change).
