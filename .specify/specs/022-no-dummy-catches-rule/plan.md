# Implementation Plan: 022-no-dummy-catches-rule

## Summary

Two changes: walk every classlike (`Class_`, `Trait_`, `Enum_`) in the file rather than only the first via `getRootNode`, and exempt single-throw catch blocks where the throw expression is a `new SomeClass(...)` (wrapping). Other dummy patterns are preserved.

## Phase 1 — Design

### Rule logic (after change)

```
validate($node):
  for each $classNode in getClassLikeNodes($node):
    foreach getMethodNodes($classNode) as $method:
      foreach NodeFinder->findInstanceOf($method->stmts, Catch_::class) as $catch:
        $error = validateCatchBlock($catch)
        if $error: emit

validateCatchBlock($catch):
  if $catch->stmts === []:                        emit empty
  if 1 stmt && Return_:                            emit return-only
  if 1 stmt && Expression(Throw_($expr)):
    if $expr instanceof New_: SKIP (wrapping)      # NEW
    else:                                          emit throw-only
  return null
```

### Tests

| # | Method | Fixture | Expected |
|---|---|---|---|
| 1 | `caso_positivo_catch_vacio` | `Jobs/CleanOldProducts.php` | 1 error |
| 2 | `caso_negativo_handling_completo` | `Jobs/ValidExceptionHandling.php` | 0 errors |
| 3 | `falso_positivo_throw_wrapping_no_es_dummy` | `Jobs/WrappingExceptionJob.php` | 0 errors |
| 4 | `falso_negativo_segundo_class_con_catch_vacio_en_archivo_multi_clase` | `Jobs/MultiClassDummyCatch.php` | 1 error |

### Documentation

`src/Rules/Smells/documentation.md` (or in `CLAUDE.md` if no per-rule docs) — note the wrapping exemption and multi-class walking.

## Tasks (see tasks.md)
