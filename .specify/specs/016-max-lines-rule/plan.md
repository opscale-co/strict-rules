# Implementation Plan: 016-max-lines-rule

## Summary

Switch `MaxLinesRule` from FileNode-level line counting to per-classlike line counting, and walk every `Class_`/`Trait_`/`Enum_` declaration in the file. Default threshold stays 500. Tests are restructured into the canonical four scenarios with two new fixtures.

## Phase 1 — Design

### Rule logic (after change)

```
validate($node):
  for each $classNode in getClassLikeNodes($node):
    if !$classNode->namespacedName instanceof Name: continue
    $lines = $classNode->getEndLine() - $classNode->getStartLine() + 1
    if $lines <= $maxLines: continue
    emit error with $classNode->namespacedName, $lines, $maxLines, line=$classNode->getEndLine()

getClassLikeNodes($node):
  walks the namespace's stmts collecting Class_, Trait_, and Enum_ nodes
```

### Tests (threshold 25 in test setup)

| # | Method | Fixture | Expected |
|---|---|---|---|
| 1 | `caso_positivo_clase_excede_limite` | `Models/User.php` | 1 error |
| 2 | `caso_negativo_clase_dentro_del_limite` | `Models/ValidSmallUser.php` | 0 errors |
| 3 | `falso_positivo_clase_pequena_con_muchos_imports` | `Models/SmallClassWithManyImports.php` | 0 errors |
| 4 | `falso_negativo_dos_clases_grandes_en_archivo_multi_clase` | `Models/MultiClassFatClasses.php` | 2 errors |

### Documentation

`src/Rules/SOLID/SRP/documentation.md` — refresh `MaxLinesRule` block with class-level measurement and multi-class walking.

## Tasks (see tasks.md)
