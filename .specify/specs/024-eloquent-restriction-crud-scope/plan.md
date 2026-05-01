# Implementation Plan: 024-eloquent-restriction-crud-scope

## Summary

Narrow `EloquentRestrictionRule`'s curated method list to CRUD-only operations. Relationships, collection iteration, model state, timestamps utilities, events and serialization helpers are removed from the list — they are legitimate Model concerns. Detection mechanics, namespace gating and diagnostic identifier are preserved.

## Phase 1 — Design

### Rule logic (after change)

```
getEloquentMethods():
  return CRUD list only:
    - query builder (where, orderBy, joins, limit, select, withTrashed, lockForUpdate, raw)
    - retrieval (get, first*, find*, all, value, pluck, sole)
    - pagination (paginate, simplePaginate, cursorPaginate)
    - aggregates (count, sum, avg, min, max, exists, doesntExist)
    - persistence (create, insert*, update*, upsert, increment, decrement, save*, delete, destroy, forceDelete, restore)
```

Detection (`isDirectModelStaticCall`, `isSelfStaticCallInModel`, `isThisCallInModel`) untouched.

### Tests

| # | Method | Fixture | Expected |
|---|---|---|---|
| 1 | `caso_positivo_eloquent_calls_en_modelo` | `Models/Product.php` | 2 errors (was 3 — `belongsTo` no longer flags) |
| 2 | `caso_negativo_eloquent_calls_en_repository_y_service` | `Models/Repositories/ProductRepository.php` + `Services/CrossEntityService.php` | 0 errors |
| 3 | `falso_positivo_self_find_en_clase_no_eloquent` | `Domain/Locator.php` | 0 errors |
| 4 | `falso_positivo_relaciones_y_estado_en_modelo` *(new)* | `Models/RelationsOnlyModel.php` *(new fixture)* | 0 errors |
| 5 | `falso_negativo_eloquent_call_en_controller` | `Models/User.php` + `Http/UserController.php` | 1 error |

### Documentation

- `src/Rules/DDD/Repositories/documentation.md` — Description, Justification and the Condition row in the property table updated to reflect CRUD-only scope. Examples cite `find`/`create`/`save`/`delete` as flagged and `belongsTo`/`with`/`load`/`toArray` as not flagged.
- `README.md` rule-catalogue row narrowed.
- Class PHPDoc updated.

## Phase 2 — Risks

- **Consumer baselines that asserted relationship-method violations will now produce phantom-not-found entries.** Consumers should regenerate their baselines. The diagnostic identifier is preserved so identifier-keyed baselines survive cleanly.

## Tasks (see tasks.md)
