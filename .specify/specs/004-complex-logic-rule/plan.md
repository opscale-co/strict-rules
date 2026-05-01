# Implementation Plan: 004-complex-logic-rule

**Branch**: `004-complex-logic-rule`
**Spec**: `.specify/specs/004-complex-logic-rule/spec.md`

## Summary

Replace `ComplexLogicRule`'s import-counting heuristic with an AST-driven operation count, and re-anchor the rule's scope on `\Services\*` exemption rather than `\Models` containment. The new rule walks every method body once, collects distinct Eloquent model FQCNs that appear as the target of a `StaticCall`, a `New_`, or a `->save()` on a typed parameter, and flags any class outside `\Services\*` whose distinct count exceeds two.

## Technical Context

- **Language**: PHP 8.2
- **Static analyser**: PHPStan + Larastan, level 5 (level 8 target)
- **Test runner**: Pest 3.8 over PHPStan's `RuleTestCase`
- **AST library**: `nikic/php-parser`
- **Diagnostic identifier**: `ddd.domainServices.complexLogic` (preserved)
- **Threshold**: `> 2` distinct Eloquent model FQCNs operated upon
- **Persistence methods recognised**: `save` only, on a typed parameter receiver

## Project Structure

```
src/Rules/DDD/DomainServices/
├── ComplexLogicRule.php       ← REWRITTEN — extends BaseRule, AST counter, threshold 2
└── documentation.md            ← MODIFIED — new metric, threshold, scope

tests/fixtures/
├── Jobs/
│   ├── MultiModelJob.php      ← NEW — positive (3 distinct ops)
│   └── FQCNDirectJob.php       ← NEW — false-negative (3 ops, FQCNs, no imports)
├── Services/
│   └── CrossEntityService.php  ← NEW — negative (Service, 4 ops, exempt)
└── Http/
    └── MultiModelFormRequest.php ← NEW — false-positive (type hints + class refs only)

tests/Rules/
└── ComplexLogicTest.php        ← REWRITTEN — 4 named scenarios
```

## Phase 1 — Design

### Rule logic (after change)

```
shouldProcess($node, $scope):
  if !BaseRule::shouldProcess return false   # not anonymous, not interface, has reflection
  $rootNode = getRootNode($node)
  if $rootNode is Enum_ return false
  $namespace = getNamespace($node)
  if isInNamespaces($namespace, ['\\Services']) return false  # Services exempt
  return true

validate($node):
  $rootNode = getRootNode($node)
  $models = []   # set of FQCN
  for each method on $rootNode:
    $paramTypes = indexParamTypes($method)         # name -> FQCN
    walk method body (single NodeTraverser pass):
      on StaticCall with Name class:
        $fqcn = $node->class->toString()
        if isEloquentModel($fqcn): $models[$fqcn] = true
      on New_ with Name class:
        same
      on MethodCall with name 'save' and Variable receiver:
        if $paramTypes[$receiver->name] is Eloquent Model: $models[that FQCN] = true
  if count($models) > 2:
    emit error: "Class \"X\" performs operations on N distinct Eloquent models (A, B, C). ..."
```

The error message:

> Class "Opscale\Jobs\MultiModelJob" performs operations on 3 distinct Eloquent models (Opscale\Models\Product, Opscale\Models\Tenant, Opscale\Models\User). Complex logic involving more than 2 entities must live in a class under `\Services\` (typically an Opscale Action under `\Services\Actions\`).

Sorted FQCN list ensures deterministic test assertions.

### Test scenarios

| # | Method name | Fixtures | Expected errors |
|---|---|---|---|
| 1 | `caso_positivo_tres_modelos_en_operaciones` | `Jobs/MultiModelJob.php` + Models User, Product, Tenant | 1 — at class line, message lists all three |
| 2 | `caso_negativo_service_con_muchos_modelos` | `Services/CrossEntityService.php` + 4 models | 0 |
| 3 | `falso_positivo_solo_type_hints_y_class_refs` | `Http/MultiModelFormRequest.php` + 3 models | 0 |
| 4 | `falso_negativo_fqcn_en_linea_sin_imports` | `Jobs/FQCNDirectJob.php` + 3 models | 1 |

### Documentation update

`src/Rules/DDD/DomainServices/documentation.md` table row:

| Property | Value |
|---|---|
| Rule Name | `ComplexLogicRule` |
| Identifier | `ddd.domainServices.complexLogic` |
| Scope | Class-level. Applies to every class outside `\Services\*` (including `\Services\Actions\*`). |
| Condition | A class MUST NOT operate on more than 2 distinct Eloquent models. Operations counted: `StaticCall` on a Model class, `new Model(...)`, `->save()` on a parameter typed as a Model. Mere references — `Model::class`, type hints, return types, `instanceof` — are not operations and do not count. |

## Phase 2 — Tasks (see `tasks.md`)

## Risks & Mitigations

| Risk | Mitigation |
|---|---|
| Walking each method body with a NodeTraverser is more verbose than NodeFinder | A single visitor handles all three operation types in one pass. |
| Param-type lookup may miss instance saves on properties | Documented as v1 limitation; out of scope. |
| Consumers with multi-model operations in non-Service classes (controllers, jobs) get many new errors | This is the intended behaviour — Article V of the constitution. Documented as BREAKING CHANGE. |
| The legacy import-counting fixture `tests/fixtures/Models/Repositories/UserRepository.php` (line 8 expectation) is now invalid because the trait does not perform any StaticCall on a Model FQCN | Remove the legacy fixture from the test; the new positive fixture (`Jobs/MultiModelJob.php`) is what the rule fires on. |

## Out of Scope

- Persistence methods other than `save` (`delete`, `update`, `restore`, `forceDelete`, `fill`).
- Property-typed receivers (`$this->user->save()`).
- MethodCall chains where the static call's return type is a Model (`User::find($id)->save()`).
- Counting reads vs writes separately.
