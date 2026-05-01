# Tasks: 012-communication-layer-rule

## T001 — Add fixture: `tests/fixtures/Observers/ValidObserver.php`
Observer under `Opscale\Observers` importing `Illuminate\Database\Eloquent\Model` (typing), `Log`, `Event`, `Broadcast` facades, an `Illuminate\Contracts\Broadcasting\ShouldBroadcast` contract, and a project Model from `\Opscale\Models\*`.

## T002 — Add fixture: `tests/fixtures/Observers/DbAccessObserver.php` [P]
Observer under `Opscale\Observers` importing `Illuminate\Support\Facades\DB` (Representation-only). Used by falso_negativo.

## T003 — Modify `src/Rules/CLEAN/Communication/CommunicationLayerRule.php`
Add `'Illuminate\\Database\\Eloquent\\'` to allowed framework imports. Add `'Log'` to allowed facades.

## T004 — Rewrite `tests/Rules/CommunicationLayerTest.php`
Four canonical scenarios.

## T005 — Update `src/Rules/CLEAN/Communication/documentation.md`
Add `Illuminate\Database\Eloquent\` and `Log` to the allowed-imports table.

## T006 — Verify
`npm test` green. Commit `feat(rules):` (no `!` — additive change).
