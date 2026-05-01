# Implementation Plan: 023-helpers-restriction-rule

## Summary

Walk every classlike (`Class_`, `Trait_`, `Enum_`) in the file. Detection logic for chained helper calls and standalone helper calls is preserved.

## Phase 1 — Design

### Rule logic (after change)

```
validate($node):
  for each $classNode in getClassLikeNodes($node):
    foreach getMethodNodes($classNode) as $method:
      run existing chained-helper detection
      run existing standalone-helper detection
```

### Tests

| # | Method | Fixture | Expected |
|---|---|---|---|
| 1 | `caso_positivo_helpers_chained_y_standalone` | `Classes/ClassWithHelpers.php` | 3 errors |
| 2 | `caso_negativo_clase_sin_helpers` | `Classes/ClassWithoutHelpers.php` | 0 errors |
| 3 | `falso_positivo_static_calls_y_facades_no_son_helpers` | `Classes/ClassWithStaticMethods.php` | 0 errors |
| 4 | `falso_negativo_helpers_en_segunda_clase_de_archivo_multi_clase` | `Classes/MultiClassWithHelpers.php` | 1 error |

## Tasks (see tasks.md)
