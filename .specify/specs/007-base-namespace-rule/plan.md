# Implementation Plan: 007-base-namespace-rule

## Summary

Refactor `BaseNamespaceRule` to walk every class declared in a file (not just the first) so multi-class files no longer slip through. The namespace check itself stays as `str_ends_with($namespace, '\\Models')` per the user's clarification: models must live directly under `\Models` — no subfolders, no nested aggregate-grouping subnamespaces. Switch the rule's base from `DomainRule` to `BaseRule` so we are not double-filtered by `\Models\*` first.

## Phase 1 — Design

### Rule logic (after change)

```
shouldProcess($node, $scope):
  return $node instanceof FileNode

validate($node):
  $namespace = getNamespace($node)
  $errors = []
  for each $classNode in getClassNodes($node):
    if !$classNode->namespacedName instanceof Name: continue
    $fqcn = $classNode->namespacedName->toString()
    if !isEloquentModelClassName($fqcn): continue
    if str_ends_with($namespace, '\\Models'): continue  // valid
    $errors[] = build error at $classNode->getLine() naming $fqcn
  return $errors

isEloquentModelClassName($fqcn):
  if !reflectionProvider->hasClass($fqcn): false
  $r = reflectionProvider->getClass($fqcn)
  return $r->getName() === Model::class || $r->isSubclassOf(Model::class)
```

### Tests

| # | Method name | Fixtures | Expected |
|---|---|---|---|
| 1 | `caso_positivo_modelo_eloquent_fuera_de_models` | `Domain/User.php` | 1 error at the class line |
| 2 | `caso_negativo_modelo_eloquent_en_models` | `Models/ValidUlidUser.php` | 0 errors |
| 3 | `falso_positivo_clase_no_eloquent_fuera_de_models` | `Domain/JustAHelper.php` | 0 errors |
| 4 | `falso_negativo_segunda_clase_eloquent_en_archivo_multi_clase` | `Domain/MultiClassFile.php` | 1 error at the second class line |

### Documentation

`src/Rules/DDD/Subdomains/documentation.md` — refresh `BaseNamespaceRule` block:
- Identifier row.
- Reword Condition to "Eloquent models must live directly under a `\Models` namespace; nested subfolders are not allowed; the rule walks every class declared in the file".

## Tasks (see tasks.md)
