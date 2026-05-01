# Implementation Plan: 010-no-accesor-mutator-rule

## Summary

Tighten `NoAccesorMutatorRule` so it (a) drops the redundant string-prefix/suffix fallback that mis-flagged Eloquent's own `getAttribute`/`setAttribute` overrides, (b) requires the exact Laravel FQCN `Illuminate\Database\Eloquent\Casts\Attribute` instead of a loose `\Attribute` suffix, and (c) walks every Class_ in the file rather than only the first. Switch the base from `DomainRule` to `BaseRule` so the per-class shape filtering can run inside `validate`.

## Phase 1 — Design

### Rule logic (after change)

```
shouldProcess($node, $scope):
  if !$node instanceof FileNode: false
  $namespace = getNamespace($node)
  return isInNamespaces($namespace, ['\\Models'])

validate($node):
  $errors = []
  for each $classNode in getClassNodes($node):
    if !$classNode->namespacedName instanceof Name: continue
    $fqcn = $classNode->namespacedName->toString()
    if !isEloquentModelClassName($fqcn): continue
    for each $method in getMethodNodes($classNode):
      if isMutator($method) || isAccessor($method) || isAttributeMethod($method):
        $errors[] = build error at $method->getLine()
  return $errors

isMutator($m):
  return preg_match('/^set[A-Z]\w*Attribute$/', $m->name->toString()) === 1

isAccessor($m):
  return preg_match('/^get[A-Z]\w*Attribute$/', $m->name->toString()) === 1

isAttributeMethod($m):
  if $m->returnType instanceof Name && $m->returnType->toString() === ATTRIBUTE_FQCN:
    return true
  for each $stmt in $m->stmts:
    if $stmt instanceof Return_ && $stmt->expr instanceof StaticCall
       && $stmt->expr->class instanceof Name
       && $stmt->expr->class->toString() === ATTRIBUTE_FQCN:
      return true
  return false

ATTRIBUTE_FQCN = 'Illuminate\\Database\\Eloquent\\Casts\\Attribute'
```

### Tests

| # | Method | Fixtures | Expected |
|---|---|---|---|
| 1 | `caso_positivo_modelo_con_accesores_mutadores_y_attribute` | `Product.php` | 3 errors at lines 22, 27, 32 |
| 2 | `caso_negativo_modelo_sin_accesores_ni_mutadores` | `ValidUlidUser.php` | 0 errors |
| 3 | `falso_positivo_overrides_de_metodos_eloquent_infraestructura` | `InfrastructureOverrideModel.php` | 0 errors |
| 4 | `falso_negativo_mutador_en_segunda_clase_de_archivo_multi_clase` | `MultiModelWithMutator.php` | 1 error |

### Documentation

`src/Rules/DDD/ValueObjects/documentation.md` — refresh `NoAccesorMutatorRule` block with identifier, exact-FQCN check, drop of fallback, multi-class walking, and clear scope.

## Tasks (see tasks.md)
