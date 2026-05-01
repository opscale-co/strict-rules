# Tasks: 006-eloquent-restriction-rule

## T001 — Add fixture: `tests/fixtures/Domain/Locator.php`
Non-Eloquent class with a `find()` method and a `locate()` method that calls `self::find($key)`. Used to verify the false-positive scenario.

## T002 — Add fixture: `tests/fixtures/Http/UserController.php` [P]
Controller-style class outside `\Services\*` that imports `Opscale\Models\User` and calls `User::find($id)`. Used to verify the false-negative scenario.

## T003 — Rewrite `src/Rules/DDD/Repositories/EloquentRestrictionRule.php`
- Extend `BaseRule`.
- New shouldProcess: skip enums + `\Models\Repositories\*` + `\Services\*`.
- Update message text to mention both allowed namespaces.
- Tighten static-self check: only flag when the enclosing class is an Eloquent Model.
- `declare(strict_types=1)`.

## T004 — Rewrite `tests/Rules/EloquentRestrictionTest.php`
Four `#[Test]` methods covering the scenarios above.

## T005 — Update `src/Rules/DDD/Repositories/documentation.md`
Add identifier row and update the condition wording.

## T006 — Verify
`npm test` green. Commit `feat(rules)!:` with `BREAKING CHANGE:` footer.
