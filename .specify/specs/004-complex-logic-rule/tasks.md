# Tasks: 004-complex-logic-rule

## T001 — Add fixture: `tests/fixtures/Jobs/MultiModelJob.php`

Non-Service class (`Opscale\Jobs\MultiModelJob`) whose `handle()` body contains:
- `User::find($userId)` — `StaticCall` on `User`
- `$product->save()` where `$product` is a typed parameter `Product $product`
- `new Tenant([...])` — `New_` of `Tenant`

Total: 3 distinct Eloquent models operated upon. Used as positive case.

## T002 — Add fixture: `tests/fixtures/Services/CrossEntityService.php` [P]

Service class (`Opscale\Services\CrossEntityService`) whose `orchestrate()` body executes static calls on four distinct Eloquent models. Used as negative case (exempt by `\Services\*` scope).

## T003 — Add fixture: `tests/fixtures/Http/MultiModelFormRequest.php` [P]

Non-Service class (`Opscale\Http\MultiModelFormRequest`) with three parameter type hints to Models, three `Model::class` constant references in a property, and zero static calls / `new` / `->save()` invocations on those models. Used as false-positive case.

## T004 — Add fixture: `tests/fixtures/Jobs/FQCNDirectJob.php` [P]

Non-Service class (`Opscale\Jobs\FQCNDirectJob`) whose `handle()` body contains three `StaticCall` expressions written with full FQCNs (`\Opscale\Models\User::find(1)`, etc.) and **no** `use` imports. Used as false-negative case.

## T005 — Rewrite `src/Rules/DDD/DomainServices/ComplexLogicRule.php`

- Extend `BaseRule` instead of `DomainRule`.
- New `shouldProcess`: skip enums, anonymous, interfaces, and any class under `\Services\*`.
- New `validate`: traverse each method body once with a `NodeVisitor` that collects FQCNs from `StaticCall->class`, `New_->class`, and `MethodCall->save` whose receiver matches a typed parameter. Reflect once per FQCN to confirm it subclasses `Illuminate\Database\Eloquent\Model`.
- Threshold: `count(distinct) > 2`.
- New error message including count and sorted FQCN list.
- Preserve `ddd.domainServices.complexLogic` identifier.
- `declare(strict_types=1)`.

## T006 — Rewrite `tests/Rules/ComplexLogicTest.php`

Four `#[Test]` methods covering the scenarios in the spec. Use `sprintf` with a shared message template that includes count and FQCN list. Drop the legacy fixture-pair from the previous test (`UserRepository.php` + `BatchingService.php`).

## T007 — Update `src/Rules/DDD/DomainServices/documentation.md`

- Add identifier row.
- Replace the "Multiple model dependencies allowed only in `*Service` classes" condition with the new operation-based, threshold-2, namespace-`\Services\*` description.

## T008 — Verify

- `npm test` green (95 → 98 expected: -1 old test removed, +4 new).
- Commit `feat(rules)!: ...` with `BREAKING CHANGE:` footer about the new metric and the relaxed threshold.
