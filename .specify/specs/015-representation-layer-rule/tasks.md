# Tasks: 015-representation-layer-rule

## T001 — Add fixture: `tests/fixtures/Models/CarbonTypedModel.php`
Eloquent Model under `Opscale\Models` that uses HasUlids trait and imports `Illuminate\Support\Carbon` for typing.

## T002 — Add fixture: `tests/fixtures/Models/StorageUsingModel.php` [P]
Model under `Opscale\Models` importing `Illuminate\Support\Facades\Storage` (Transformation-only). Used by falso_negativo regression.

## T003 — Modify `src/Rules/CLEAN/Representation/RepresentationLayerRule.php`
Add `'Illuminate\\Support\\Carbon'` to allowedFrameworkImports. Trait/Model auto-allow override unchanged.

## T004 — Rewrite `tests/Rules/RepresentationLayerTest.php`
Four canonical scenarios.

## T005 — Update `src/Rules/CLEAN/Representation/documentation.md`
Add `Illuminate\Support\Carbon` to allowed framework imports and note the package-agnostic root.

## T006 — Verify
`npm test` green. Commit `feat(rules):` (no `!` — additive change).
