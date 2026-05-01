# Tasks: 024-eloquent-restriction-crud-scope

## T001 — Modify `src/Rules/DDD/Repositories/EloquentRestrictionRule.php`
Replace `getEloquentMethods()` with the CRUD-only list. Update the class PHPDoc to describe the new scope. Detection mechanics, namespace gating and identifier `ddd.repositories.eloquentRestriction` preserved.

## T002 — Add fixture `tests/fixtures/Models/RelationsOnlyModel.php`
Eloquent model declaring `belongsTo`, `hasMany`, calling `$this->load(...)`, `$this->getAttributes()`, `$this->toArray()`, `$this->refresh()`. PSR-4 autoloaded via existing `Opscale\` mapping — no classmap needed (single class file).

## T003 — Update `tests/Rules/EloquentRestrictionTest.php`
- `caso_positivo`: drop the `belongsTo` line-41 expectation; assert 2 errors (the two `where` calls).
- Keep `caso_negativo` and `falso_positivo_self_find_en_clase_no_eloquent`.
- Keep `falso_negativo_eloquent_call_en_controller`.
- Add `falso_positivo_relaciones_y_estado_en_modelo` analysing `RelationsOnlyModel.php` with zero expected errors.

## T004 — Update docs
- `src/Rules/DDD/Repositories/documentation.md` — Description, Justification and Condition row narrowed to CRUD scope. Examples cite which APIs flag and which don't.
- `README.md` rule-catalogue row narrowed.

## T005 — Verify
- `composer dump-autoload`
- `vendor/bin/pest tests/Rules/EloquentRestrictionTest.php` green
- `npm test` green (110 tests)
- `npm run analyse` clean

## T006 — Commit
`feat(rules)!: narrow EloquentRestrictionRule to CRUD operations` — BREAKING CHANGE footer documenting that relationship/state/serialization calls on Models no longer flag.
