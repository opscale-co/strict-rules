# Implementation Plan: 009-enforce-cast-rule

## Summary

Make `EnforceCastRule`'s contract check transitive (via `ClassReflection::getInterfaces()` instead of literal `implements` clause inspection) and walk every class declaration in the file. Switch the base from `DomainRule` to `BaseRule` so we can run a tighter `shouldProcess` (namespace under `\Models\ValueObjects\*`) and a permissive `validate` (per-class filtering).

## Phase 1 — Design

### Rule logic (after change)

```
shouldProcess($node, $scope):
  if !$node instanceof FileNode: false
  $namespace = getNamespace($node)
  return isInNamespaces($namespace, ['\\Models\\ValueObjects'])

validate($node):
  $errors = []
  for each $classNode in getClassNodes($node):
    if !$classNode->namespacedName instanceof Name: continue
    if $classNode->isAbstract(): continue
    $fqcn = $classNode->namespacedName->toString()
    if !$reflectionProvider->hasClass($fqcn): continue
    $reflection = $reflectionProvider->getClass($fqcn)
    if implementsCastsAttributes($reflection): continue
    $errors[] = build error at $classNode->getLine() naming $fqcn
  return $errors

implementsCastsAttributes($reflection):
  foreach $reflection->getInterfaces() as $interface:
    if $interface->getName() === CastsAttributes::class: return true
  return false
```

### Tests (consolidate 5 → 4)

| # | Method | Fixtures | Expected |
|---|---|---|---|
| 1 | `caso_positivo_value_object_sin_casts_attributes` | `Address.php` | 1 error at the class line |
| 2 | `caso_negativo_value_object_directo_y_heredado` | `ValidAddress.php` + `AbstractCastableValueObject.php` + `InheritingValueObject.php` | 0 errors |
| 3 | `falso_positivo_value_object_con_interface_heredada` | `AbstractCastableValueObject.php` + `InheritingValueObject.php` | 0 errors (today flag) |
| 4 | `falso_negativo_segunda_clase_sin_interface_en_archivo_multi_clase` | `MultiVOFile.php` (FirstVO valid + SecondVO invalid) | 1 error |

### Documentation

`src/Rules/DDD/ValueObjects/documentation.md` — refresh `EnforceCastRule` block with identifier, transitive-check description, multi-class walking note, abstract-skip note.

## Tasks (see tasks.md)
