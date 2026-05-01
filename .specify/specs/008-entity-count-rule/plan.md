# Implementation Plan: 008-entity-count-rule

## Summary

Replace the broken static-counter implementation of `EntityCountRule` with a proper PHPStan two-phase pipeline: a `Collector<FileNode, array{namespace, fqcn, file}>` that records every concrete Eloquent model, and a `Rule<CollectedDataNode>` that groups by file-level namespace and emits one error per subdomain over `maxClasses`. Default bumped from 10 to 25 per the user's clarification.

## Phase 1 — Design

### Collector

```php
final class EntityCountCollector implements Collector<FileNode, array{namespace: string, fqcn: string, file: string}> {
    constructor(ReflectionProvider)
    getNodeType(): FileNode
    processNode(FileNode, Scope):
      $records = []
      foreach getClassNodes($node) as $classNode:
        if no namespacedName: continue
        $fqcn = $classNode->namespacedName->toString()
        if not isConcreteEloquentModel($fqcn): continue
        $records[] = [namespace: getNamespace($node), fqcn: $fqcn, file: $scope->getFile()]
      return $records !== [] ? $records : null
}
```

### Rule

```php
final class EntityCountRule implements Rule<CollectedDataNode> {
    constructor(int $maxClasses = 25)
    getNodeType(): CollectedDataNode
    processNode(CollectedDataNode):
      $bySubdomain = []
      $files = []
      foreach $node->get(EntityCountCollector::class) as $perFile:
        foreach $perFile as $record:
          $bySubdomain[$record['namespace']][] = $record['fqcn']
          $files[$record['namespace']][] = $record['file']
      $errors = []
      foreach $bySubdomain as $namespace => $fqcns:
        $unique = array_unique($fqcns)
        if count($unique) > $maxClasses:
          $errors[] = build error pointing at $files[$namespace][0], line 1
      return $errors
}
```

### Tests (with `maxClasses = 2`)

| # | Method | Fixtures | Expected |
|---|---|---|---|
| 1 | `caso_positivo_subdomain_excede_limite` | Tenant + Product + ValidatedModel (all `\Opscale\Models`) | 1 error: 3 entities > 2 |
| 2 | `caso_negativo_subdomain_dentro_del_limite` | Tenant + Product (both `\Opscale\Models`) | 0 |
| 3 | `falso_positivo_clases_no_eloquent_no_cuentan` | NonModelClass + EmptyClass + AbstractModel + TestInterface | 0 |
| 4 | `falso_negativo_subdominios_no_se_lumpean` | Tenant + Product (`\Opscale\Models`) + FooEntity (`\Opscale\Modules\Foo\Models`) | 0 — each subdomain at/below threshold |

### NEON registration

```neon
- 
    class: Opscale\Rules\DDD\Subdomains\EntityCountCollector
    tags:
        - phpstan.collector
- 
    class: Opscale\Rules\DDD\Subdomains\EntityCountRule
    tags:
        - phpstan.rules.rule
```

### Documentation

`src/Rules/DDD/Subdomains/documentation.md` — refresh `EntityCountRule` block to describe the collector + per-subdomain aggregation, default 25, and `> maxClasses` semantics.

## Tasks (see tasks.md)
