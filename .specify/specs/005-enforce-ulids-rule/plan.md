# Implementation Plan: 005-enforce-ulids-rule

## Summary

Make `EnforceUlidsRule` recognise `Illuminate\Database\Eloquent\Concerns\HasUlids` regardless of which `TraitUse` statement declares it, walk the inheritance chain so children of validated parents are accepted, and detect disablement via the `$incrementing` and `$keyType` property overrides. Consolidate the existing five tests into the canonical four scenarios.

## Phase 1 — Design

### Rule logic (after change)

```
shouldProcess unchanged (DomainRule, must be Eloquent model)

validate($node):
  $rootNode = getRootNode($node)
  if !$rootNode instanceof Class_: return []

  $reflection = getClassReflection($node)
  $hasUlids = $reflection !== null && $this->hasUlidsInChain($reflection)

  if !$hasUlids:
    return [missingTraitError($rootNode)]

  if $this->disablesUlid($rootNode):
    return [disabledTraitError($rootNode)]

  return []

hasUlidsInChain($reflection):
  for each $current in [$reflection, ...$reflection.getParents()]:
    if HasUlids::class in $current.getNativeReflection().getTraitNames(): return true
  return false

disablesUlid($classNode):
  for each $stmt in $classNode->stmts:
    if !$stmt instanceof Property: continue
    for each $prop in $stmt->props:
      $name = $prop->name->toString()
      $default = $prop->default

      if $name === 'incrementing'
         && $default instanceof ConstFetch
         && strtolower($default->name->toString()) === 'true':
        return true

      if $name === 'keyType'
         && $default instanceof String_
         && $default->value !== 'string':
        return true

  return false
```

### Tests

| # | Method name | Fixtures | Expected |
|---|---|---|---|
| 1 | `caso_positivo_modelo_sin_trait` | `Models/User.php` | 1 error at line 13 |
| 2 | `caso_negativo_modelo_con_trait_directo_e_heredado` | `ValidUlidUser.php`, `AbstractUlidEntity.php`, `InheritingUlidEntity.php` | 0 errors |
| 3 | `falso_positivo_trait_en_segunda_declaracion` | `MultiStmtUlidModel.php` | 0 errors |
| 4 | `falso_negativo_trait_neutralizado_por_property_override` | `DisabledUlidModel.php` | 1 error with the disablement message |

### Documentation update

- Mention "any `use ...;` statement", "inheritance chain", and the `$incrementing` / `$keyType` neutralisation check.
- Add identifier row.

## Tasks (see tasks.md)
