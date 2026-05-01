# Tasks: 002-parent-child-transaction-rule

**Spec**: `.specify/specs/002-parent-child-transaction-rule/spec.md`
**Plan**: `.specify/specs/002-parent-child-transaction-rule/plan.md`

## T001 — Add fixture: `tests/fixtures/Models/Tenant.php`

Eloquent `Model` under `\Opscale\Models` with no `belongsTo` and no other relationship methods. Used as the "negative" case (aggregate root saved through its repository → no error).

## T002 — Add fixture: `tests/fixtures/Models/AbstractChildEntity.php` [P]

Abstract `Model` declaring `public function parent(): BelongsTo` (typed return). Used as the parent in the false-negative scenario.

## T003 — Add fixture: `tests/fixtures/Models/InheritingChildEntity.php`

Concrete subclass of `AbstractChildEntity`, with no relationship methods of its own. Inherits the `belongsTo` from its parent.

## T004 — Add fixture: `tests/fixtures/Models/OrgPolicy.php` [P]

Eloquent `Model` whose `isAuthorizedFor()` method body contains a non-Eloquent `$group->belongsTo($actor)` call. The model declares **no** method with a `BelongsTo` return type. Used as the false-positive scenario fixture.

## T005 — Add fixture: `tests/fixtures/Models/Repositories/TenantRepository.php` [P]

Trait under `\Opscale\Models\Repositories` with a method that takes a `Tenant $tenant` parameter and calls `$tenant->save()`. Used by T009 scenario 2.

## T006 — Add fixture: `tests/fixtures/Models/Repositories/InheritingChildRepository.php` [P]

Trait under `\Opscale\Models\Repositories` with a method that takes an `InheritingChildEntity $child` parameter and calls `$child->save()`. Used by T009 scenario 4.

## T007 — Add fixture: `tests/fixtures/Models/Repositories/OrgPolicyRepository.php` [P]

Trait under `\Opscale\Models\Repositories` with a method that takes an `OrgPolicy $policy` parameter and calls `$policy->save()`. Used by T009 scenario 3.

## T008 — Modify `src/Rules/DDD/Aggregates/ParentChildTransactionRule.php`

- Replace `isBelongsToMethod()` with `isBelongsToReturn()` that inspects only the `returnType` and accepts `BelongsTo`, `MorphTo`, plus their FQCNs.
- Replace `modelHasParent($className)` with a chain-walking version that iterates `[reflection, ...reflection.getParents()]` and inspects each ancestor's AST via `getASTForClass()`.
- Remove the body-scan branch entirely.
- Preserve diagnostic identifier and error message text.

## T009 — Rewrite `tests/Rules/ParentChildTransactionTest.php`

Four `#[Test]` methods:

1. `caso_positivo_save_directo_en_hijo` — analyses `ProductRepository.php`. Expects 1 error at line 12 with the existing message.
2. `caso_negativo_save_en_aggregate_root` — analyses `TenantRepository.php` + `Tenant.php`. Expects 0 errors.
3. `falso_positivo_belongsTo_helper_no_relacional` — analyses `OrgPolicyRepository.php` + `OrgPolicy.php`. Expects 0 errors.
4. `falso_negativo_belongsTo_heredado` — analyses `InheritingChildRepository.php` + `InheritingChildEntity.php` + `AbstractChildEntity.php`. Expects 1 error.

## T010 — Update `src/Rules/DDD/Aggregates/documentation.md`

Update the `ParentChildTransactionRule` table row to:
- Add `Identifier: ddd.aggregates.parentChildTransaction`.
- Update Scope to "Method-level inside `\Models\Repositories` and `\Services`".
- Update Condition to specify the return-type-only rule and inheritance-chain walking.

## T011 — Verify

- Run `npm test`. Expected: 90 → 93 tests passing (4 scenarios in `ParentChildTransactionTest`, was 1; net +3).
- Confirm no other rule tests regressed.
- Stage and commit as `feat(rules)!: …` with `BREAKING CHANGE:` footer.

## Definition of Done

- [ ] T001–T007 fixtures parse cleanly.
- [ ] T008 rule passes T009 scenarios.
- [ ] T009 four-scenario test passes under Pest.
- [ ] T010 documentation updated.
- [ ] T011 commit landed.
