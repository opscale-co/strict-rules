# Implementation Plan: 020-disallow-instantiation-rule

## Summary

Two changes to `DisallowInstantiationRule`: (a) accept any class whose reflection is a subclass of one of four canonical Laravel base classes (Eloquent Model, Mailable, Notification, JsonResource), and (b) walk every classlike (Class_, Trait_, Enum_) in the file rather than only the first via `getRootNode`. Existing allowed names, suffixes and built-in handling are preserved.

## Phase 1 — Design

### Rule logic (after change)

```
validate($node):
  for each $classNode in getClassLikeNodes($node):
    foreach getMethodNodes($classNode) as $method:
      skip if $method is __construct
      foreach findNewExpressions($method) as $new:
        skip if $new->class isn't a Name
        $resolved = resolveClassName($new->class->toString(), $node)
        skip if isAllowedInstantiation($resolved)         # existing checks
        skip if isSubclassOfAllowedBase($resolved)        # NEW
        skip if isSelfOrParentInstantiation
        emit error attributed to $classNode->namespacedName

isSubclassOfAllowedBase($fqcn):
  if !$reflectionProvider->hasClass($fqcn): false
  $reflection = $reflectionProvider->getClass($fqcn)
  for each $base in [Model, Mailable, Notification, JsonResource]:
    if $reflection->getName() === $base or $reflection->isSubclassOf($base):
      return true
  return false
```

### Tests

| # | Method | Fixtures | Expected |
|---|---|---|---|
| 1 | `caso_positivo_instanciacion_de_servicio` | `Services/ExternalAPIService.php` | 1 error at line 24 |
| 2 | `caso_negativo_dependency_injection_correcta` | `Services/ValidDependencyInjection.php` | 0 errors |
| 3 | `falso_positivo_instanciacion_de_modelo_eloquent_y_mailable` | `Services/ServiceInstantiatingModel.php` + `Mail/SendOrderEmail.php` | 0 errors |
| 4 | `falso_negativo_instanciacion_en_segunda_clase_de_archivo_multi_clase` | `Services/MultiInstantiationServices.php` | 1 error |

### Documentation

`src/Rules/SOLID/DIP/documentation.md` — refresh with the new subclass-based allowance and multi-class walking.

## Tasks (see tasks.md)
