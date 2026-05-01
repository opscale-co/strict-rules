# Tasks: 013-orchestration-layer-rule

## T001 — Add fixture: `tests/fixtures/Jobs/ValidJob.php`
Job under `Opscale\Jobs` importing the Bus/Queue/Foundation framework essentials, `Illuminate\Database\Eloquent\Model` (for typing), `Log` facade, and a project Model. Used by caso_negativo + falso_positivo.

## T002 — Add fixture: `tests/fixtures/Jobs/DbAccessJob.php` [P]
Job under `Opscale\Jobs` importing `Illuminate\Support\Facades\DB` (Representation-only). Used by falso_negativo.

## T003 — Modify `src/Rules/CLEAN/Orchestration/OrchestrationLayerRule.php`
Add `'Illuminate\\Database\\Eloquent\\'` to allowed framework imports. Add `'Log'` to allowed facades.

## T004 — Rewrite `tests/Rules/OrchestrationLayerTest.php`
Four canonical scenarios.

## T005 — Update `src/Rules/CLEAN/Orchestration/documentation.md`
Add `Illuminate\Database\Eloquent\` and `Log` to the allowed-imports table.

## T006 — Verify
`npm test` green. Commit `feat(rules):` (no `!` — additive change).
