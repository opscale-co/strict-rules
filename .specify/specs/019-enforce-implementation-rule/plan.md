# Implementation Plan: 019-enforce-implementation-rule

## Summary

Walk every classlike in the file, and resolve the set of interface methods via `ClassReflection::getInterfaces()` (transitive) instead of inspecting the class's AST `implements` clause directly. Stub-detection logic (empty body / single throw / single default-value return) is preserved.

## Phase 1 — Design

### Rule logic (after change)

```
validate($node):
  for each $classNode in getClassLikeNodes($node):
    if $classNode instanceof Enum_: continue
    if !$classNode->namespacedName instanceof Name: continue
    $fqcn = $classNode->namespacedName->toString()
    if !$reflectionProvider->hasClass($fqcn): continue
    $reflection = $reflectionProvider->getClass($fqcn)
    $interfaceMethods = collect public method names from $reflection->getInterfaces()
    if $interfaceMethods === []: continue
    foreach getMethodNodes($classNode) as $method:
      if !in_array($method->name, $interfaceMethods): continue
      validateMethodImplementation(...)  # unchanged
```

### Tests

| # | Method | Fixtures | Expected |
|---|---|---|---|
| 1 | `caso_positivo_implementaciones_stub` | `Services/BatchingService.php` | 3 errors |
| 2 | `caso_negativo_implementacion_completa` | `Models/ValueObjects/ValidAddress.php` | 0 errors |
| 3 | `falso_positivo_metodo_corto_no_stub` | `Services/IntentionalShortMethodService.php` | 0 errors |
| 4 | `falso_negativo_stub_de_interface_heredada_via_clase_padre` | `Contracts/InheritedContract.php` + `Models/ConcreteParentImplementer.php` + `Models/StubChildOverrider.php` | 1 error |

### Documentation

`src/Rules/SOLID/ISP/documentation.md` — refresh with multi-class walking + transitive interface detection.

## Tasks (see tasks.md)
