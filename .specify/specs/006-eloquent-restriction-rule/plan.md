# Implementation Plan: 006-eloquent-restriction-rule

## Summary

Re-anchor `EloquentRestrictionRule` so that it applies to every class outside `\Models\Repositories\*` and `\Services\*`, with the existing detection logic unchanged in spirit but extended to handle the broader scope safely (no false positives on `self::find()` inside non-Eloquent classes).

## Phase 1 — Design

### Rule logic (after change)

```
shouldProcess($node, $scope):
  if !BaseRule::shouldProcess: false
  if rootNode is Enum_: false
  $namespace = getNamespace($node)
  if isInNamespaces($namespace, ['\\Models\\Repositories', '\\Services']): false
  return true

validate($node):
  $rootNode = getRootNode($node)
  for each method on $rootNode:
    for each Expr in method body:
      if isEloquentCall($expr, $rootNode):
        emit error

isEloquentCall($node, $rootNode):
  - StaticCall on Model FQCN with Eloquent method -> true (always)
  - StaticCall on self/static/parent with Eloquent method AND $rootNode is Model -> true
  - MethodCall on $this with Eloquent method AND $rootNode is Model -> true
  - else false
```

The Eloquent method list and helper structure are preserved.

### Tests

| # | Method name | Fixtures | Expected |
|---|---|---|---|
| 1 | `caso_positivo_eloquent_calls_en_modelo` | `Models/Product.php` | 3 errors at lines 14, 19, 41 |
| 2 | `caso_negativo_eloquent_calls_en_repository_y_service` | `Models/Repositories/ProductRepository.php` + `Services/CrossEntityService.php` | 0 errors |
| 3 | `falso_positivo_self_find_en_clase_no_eloquent` | `Domain/Locator.php` | 0 errors |
| 4 | `falso_negativo_eloquent_call_en_controller` | `Http/UserController.php` (+ `Models/User.php`) | 1 error |

### Documentation

`src/Rules/DDD/Repositories/documentation.md` table updated with the identifier and the two-namespace exemption.

## Tasks (see tasks.md)
