# Tasks: 011-interaction-layer-rule

## T001 — Add fixture: `tests/fixtures/Http/Controllers/McpInertiaController.php`
Controller importing `Mcp\Server\ServerInterface`, `Laravel\Nova\Resource`, `Inertia\Inertia`, `Laravel\Sanctum\Sanctum`, plus an allowed Auth facade and Request type. Used by caso_negativo + falso_positivo.

## T002 — Add fixture: `tests/fixtures/Http/Controllers/DbAccessController.php` [P]
Controller importing `Illuminate\Support\Facades\DB` (not allowed in Interaction). Used by falso_negativo.

## T003 — Modify `src/Rules/CLEAN/Interaction/InteractionLayerRule.php`
Add `Log` to allowed facades. Add `Inertia\\`, `Laravel\\Nova\\`, `Laravel\\Sanctum\\`, `Mcp\\`, `PhpMcp\\` to allowed external imports.

## T004 — Rewrite `tests/Rules/InteractionLayerTest.php`
Four canonical scenarios.

## T005 — Update `src/Rules/CLEAN/Interaction/documentation.md` (if it exists)
Document the new entries.

## T006 — Verify
`npm test` green. Commit `feat(rules):` (no `!` — additive change).
