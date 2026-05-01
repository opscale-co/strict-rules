# Tasks: 001-model-validation-rule

**Spec**: `.specify/specs/001-model-validation-rule/spec.md`
**Plan**: `.specify/specs/001-model-validation-rule/plan.md`

Tasks are ordered by dependency. Test tasks precede their implementation per Article X.6/7. Tag `[P]` denotes work that can run in parallel with the previous `[P]`-tagged task.

## T001 — Add fixture: `tests/fixtures/Models/ValidatedAggregateRoot.php`

Abstract Eloquent parent that uses `Opscale\Validations\Validatable`. Used to demonstrate the false-positive scenario (User Story 2).

## T002 — Add fixture: `tests/fixtures/Models/ValidatedChild.php` [P]

Concrete subclass of `ValidatedAggregateRoot`. Does NOT redeclare `use Validatable;`. Compliance is inherited.

## T003 — Add fixture: `tests/fixtures/Models/Pretenders/Validatable.php` [P]

A trait at FQCN `Opscale\Pretenders\Validatable`. Identical short name as the official one but lives in another namespace. Used by T004.

## T004 — Add fixture: `tests/fixtures/Models/PretendingValidatedModel.php`

Eloquent model under `\Opscale\Models` that imports `Opscale\Pretenders\Validatable` and uses it via `use Validatable;` inside the class body. Demonstrates the false-negative scenario (User Story 3).

## T005 — Update fixture: `tests/fixtures/Models/ValidatedModel.php`

Replace `use Enigma\ValidatorTrait;` + `use ValidatorTrait;` with `use Opscale\Validations\Validatable;` + `use Validatable;`. The class stays in `\Opscale\Models` and continues to extend `Model`. This is the "negative" case fixture.

## T006 — Modify `src/Rules/DDD/Aggregates/ModelValidationRule.php`

- Drop the `str_ends_with(..., '\\ValidatorTrait')` and bare `'ValidatorTrait'` checks.
- Match exactly the FQCN `Opscale\Validations\Validatable`.
- Walk the inheritance chain: iterate `[reflection, ...reflection.getParents()]` and inspect each ancestor's traits.
- Update the error message to reference the official package and trait.
- Keep the diagnostic identifier `ddd.aggregates.modelValidation`.
- Update class-level PHPDoc accordingly.

## T007 — Modify `tests/Rules/ModelValidationTest.php`

Replace the existing two tests with four named scenarios:

1. `caso_positivo_modelo_sin_trait` — analyses `Product.php`, expects 1 error with the new message at line 10.
2. `caso_negativo_modelo_con_trait` — analyses `ValidatedModel.php`, expects 0 errors.
3. `falso_positivo_hijo_de_padre_validado` — analyses `ValidatedAggregateRoot.php` + `ValidatedChild.php` together, expects 0 errors total. Demonstrates that today's rule WOULD have flagged the child, the new rule does NOT.
4. `falso_negativo_trait_homonimo` — analyses `PretendingValidatedModel.php` + `Pretenders/Validatable.php`, expects 1 error on the model. Demonstrates that today's rule WOULD have let it pass, the new rule catches it.

The error message asserted in (1) and (4) must contain both `Validatable` and `opscale-co/validations`.

## T008 — Update `src/Rules/DDD/Aggregates/documentation.md`

- Replace every `theriddleofenigma/laravel-model-validation` with `opscale-co/validations`.
- Replace every `ValidatorTrait` / `Enigma\ValidatorTrait` with `Validatable` / `Opscale\Validations\Validatable`.
- Update the `ModelValidationRule` row's "Condition" cell.
- Update the inline `Order` aggregate code example to use the official trait.

## T009 — Update `composer.json`

- Remove `theriddleofenigma/laravel-model-validation` from `require-dev`.
- Add `opscale-co/validations` to `require-dev` (`^1.0`). If the package is not yet on Packagist, document a Git VCS repository entry or omit the dependency line and rely on AST-only fixtures (the rule does not need the trait to resolve at runtime).

## T010 — Verify

- Run `npm test`. Expected: 88 → 90 tests passing (4 scenarios in `ModelValidationTest`, was 2; everything else unchanged).
- Run `npm run analyse` once `phpstan.neon` is comfortable at level 8 (separate concern; not blocking this feature).
- Skim `git diff` to confirm no unintended changes outside the feature scope.

## Definition of Done

- [ ] T001–T005 fixtures exist and parse cleanly.
- [ ] T006 rule emits new message and passes T007 scenarios.
- [ ] T007 four-scenario test passes under Pest.
- [ ] T008 documentation no longer mentions Enigma / ValidatorTrait.
- [ ] T009 composer.json reconciled.
- [ ] T010 `npm test` green.
