# Tasks: 008-entity-count-rule

## T001 — Add fixture: `tests/fixtures/Modules/Foo/Models/FooEntity.php`
Concrete Eloquent model under `\Opscale\Modules\Foo\Models` (a second subdomain). Used by the false-negative scenario.

## T002 — Create collector: `src/Rules/DDD/Subdomains/EntityCountCollector.php`
PHPStan `Collector<FileNode>` that records every concrete Eloquent model declared in any analysed file.

## T003 — Rewrite rule: `src/Rules/DDD/Subdomains/EntityCountRule.php`
Implements `Rule<CollectedDataNode>`. Groups collected records by file-level namespace; emits one error per subdomain over `maxClasses`. Default `maxClasses = 25`. Identifier preserved.

## T004 — Update `rules.ddd.neon`
Register the collector with tag `phpstan.collector`. Keep the rule registration with `phpstan.rules.rule`.

## T005 — Rewrite `tests/Rules/EntityCountTest.php`
Override `getCollectors()` to return the new collector. Four named scenarios using `maxClasses = 2`.

## T006 — Update `src/Rules/DDD/Subdomains/documentation.md`
Refresh the `EntityCountRule` block: identifier, default 25, collector-based architecture, per-subdomain aggregation.

## T007 — Verify
`npm test` green. Commit `feat(rules)!:` with `BREAKING CHANGE:` footer (rule actually fires now, default bumped, registration changed).
