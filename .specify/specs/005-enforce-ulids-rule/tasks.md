# Tasks: 005-enforce-ulids-rule

## T001 — Add fixture: `tests/fixtures/Models/AbstractUlidEntity.php`
Abstract Eloquent model that uses `HasUlids`. Used by the inheritance negative case.

## T002 — Add fixture: `tests/fixtures/Models/InheritingUlidEntity.php` [P]
Concrete subclass of `AbstractUlidEntity` with no inline trait declarations.

## T003 — Add fixture: `tests/fixtures/Models/MultiStmtUlidModel.php` [P]
Eloquent model with `use HasFactory, Notifiable;` and `use HasUlids;` as separate statements. False-positive scenario.

## T004 — Add fixture: `tests/fixtures/Models/DisabledUlidModel.php` [P]
Eloquent model with `use HasUlids;` plus `public $incrementing = true;` and `protected $keyType = 'int';`. False-negative scenario.

## T005 — Rewrite `src/Rules/DDD/Entities/EnforceUlidsRule.php`
- Replace single-stmt trait check with chain-walk via `ClassReflection`.
- Add property-override detection (`disablesUlid`).
- Two distinct messages, same identifier.
- `declare(strict_types=1)`.

## T006 — Rewrite `tests/Rules/EnforceUlidsTest.php`
Replace the existing five tests with the four canonical scenarios.

## T007 — Update `src/Rules/DDD/Entities/documentation.md`
Reflect the new behaviour and add the identifier row.

## T008 — Verify
`npm test` green. Commit `feat(rules)!:` with `BREAKING CHANGE:` (the disablement detection produces new errors).
