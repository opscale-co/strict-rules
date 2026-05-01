# Implementation Plan: 018-parent-call-rule

## Summary

Walk every classlike (`Class_`, `Trait_`, `Enum_`) instead of only the first via `getRootNode`. Move per-class reflection lookup inside the loop. Preserve all existing skip rules (static methods, abstract parent methods, private parent methods, `parent::` calls anywhere in body).

## Phase 1 — Design

### Rule logic (after change)

```
shouldProcess($node, $scope):
  parent::shouldProcess
  return true   # remove the single-getParentNode gate; per-class check moves to validate

validate($node):
  for each $classNode in getClassLikeNodes($node):
    if !$classNode->namespacedName instanceof Name: continue
    $fqcn = $classNode->namespacedName->toString()
    if !$reflectionProvider->hasClass($fqcn): continue
    $reflection = $reflectionProvider->getClass($fqcn)
    if $reflection->getParentClass() === null: continue
    foreach getMethodNodes($classNode) as $method:
      if $method->isStatic(): continue
      if !isOverridingParentMethod($method, $reflection): continue
      if hasParentCall($method): continue
      emit error at $method->getLine() with $fqcn
```

### Tests

| # | Method | Fixtures | Expected |
|---|---|---|---|
| 1 | `caso_positivo_override_sin_parent_call` | `Services/BatchingService.php` | 1 error at line 26 |
| 2 | `caso_negativo_override_con_parent_call` | `Models/ProperOverrider.php` + `Models/AbstractParentModel.php` | 0 errors |
| 3 | `falso_positivo_implementacion_de_metodo_abstracto` | `Models/AbstractImplementer.php` + `Models/AbstractParentModel.php` | 0 errors |
| 4 | `falso_negativo_segunda_clase_con_override_sin_parent_en_archivo_multi_clase` | `Models/MultiClassParentCall.php` + `Models/AbstractParentModel.php` | 1 error |

### Documentation

`src/Rules/SOLID/LSP/documentation.md` — refresh `ParentCallRule` block with multi-class walking.

## Tasks (see tasks.md)
