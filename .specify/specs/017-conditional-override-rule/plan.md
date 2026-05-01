# Implementation Plan: 017-conditional-override-rule

## Summary

Two changes to `ConditionalOverrideRule`: walk every classlike (`Class_`, `Trait_`, `Enum_`) instead of only the first via `getRootNode`, and skip magic methods (any method whose name starts with `__`). Update the test file from three legacy tests to the four canonical scenarios.

## Phase 1 — Design

### Rule logic (after change)

```
validate($node):
  for each $classNode in getClassLikeNodes($node):
    if !$classNode->namespacedName instanceof Name: continue
    foreach getMethodNodes($classNode) as $method:
      if !isPublicOrProtected: continue
      if $method->isAbstract(): continue
      if str_starts_with($method->name->toString(), '__'): continue   # NEW
      if $method->isFinal(): continue
      if hasOverrideAttribute($method): continue
      emit error at $method->getLine()
```

### Tests

| # | Method | Fixture | Expected |
|---|---|---|---|
| 1 | `caso_positivo_metodo_sin_final_ni_override` | `Models/Product.php` | 1 error at line 17 |
| 2 | `caso_negativo_clase_sin_metodos_publicos_violadores` | `Models/SimpleModel.php` | 0 errors |
| 3 | `falso_positivo_magic_methods` | `Models/MagicMethodsModel.php` | 0 errors |
| 4 | `falso_negativo_segunda_clase_con_metodo_sin_final_en_archivo_multi_clase` | `Models/MultiClassConditionalOverride.php` | 1 error |

### Documentation

`src/Rules/SOLID/OCP/documentation.md` — refresh `ConditionalOverrideRule` block with magic-method skip, multi-class walking, identifier.

## Tasks (see tasks.md)
