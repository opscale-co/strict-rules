# Feature Specification: EloquentRestrictionRule — CRUD-only scope

**Feature Branch**: `024-eloquent-restriction-crud-scope`
**Created**: 2026-05-01
**Status**: Draft

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Restrict CRUD calls to repositories and services (Priority: P1)

As an architect, I want PHPStan to fail when persistence and query logic leak outside `\Models\Repositories\*` or `\Services\*`. The previous iteration overreached: it also flagged relationship declarations, model-state accessors, collection iteration helpers, timestamps, events and serialization. Those are legitimate Model concerns and must NOT be confined to repositories.

**Why this priority**: Constitution Article on Repositories — persistence stays in repositories/services. The new contract narrows the rule to **only CRUD**: query building, retrieval, aggregates, persistence, soft-delete scopes, locking. Everything else the Model legitimately owns. P1.

**Acceptance Scenarios**:

1. **Caso positivo (true positive)** — **Given** the Eloquent model `Opscale\Models\Product` (outside `\Models\Repositories\*` and `\Services\*`) calls `self::where(...)` and `$this->where(...)`, **When** PHPStan analyses the file, **Then** the rule reports exactly two errors — one per `where` call. The `$this->belongsTo(...)` declaration in the same file is NOT flagged.

2. **Caso negativo (true negative)** — **Given** a Repository trait under `\Models\Repositories\*` and a Service under `\Services\*` make Eloquent calls, **When** PHPStan analyses the files, **Then** the rule reports zero errors.

---

### User Story 2 — Don't flag relationship declarations and model-state on Models (Priority: P1)

As a domain modeller, I want to declare `belongsTo`, `hasMany`, eager-load with `load`, expose state via `getAttributes`, serialize via `toArray`, and refresh via `refresh` directly inside the Model. The previous rule flagged all of these. The new rule must leave them alone.

**Why this priority**: This is the **false-positive guard** introduced by this iteration. The Model is the single source of truth for its relationships, state representation and serialization — they don't belong in a repository.

**Acceptance Scenarios**:

1. **Falso positivo a evitar (relaciones y estado en Model)** — **Given** an Eloquent model `Opscale\Models\RelationsOnlyModel` declaring `belongsTo`, `hasMany`, calling `$this->load(...)`, `$this->getAttributes()`, `$this->toArray()`, `$this->refresh()`, **When** PHPStan analyses the file, **Then** the rule reports zero errors.

2. **Falso positivo a evitar (clase no-Eloquent con métodos homónimos)** — preserved from previous iteration: `Opscale\Domain\Locator` with a `find` method does not flag.

---

### User Story 3 — Don't lose detection of CRUD calls in non-Model classes (Priority: P1)

As an architect, I want CRUD calls from a Controller/Job/Observer to still be flagged after the curated list shrinks. The smaller list is a stricter filter for "what counts as CRUD", but the locations covered (anything outside the two allowed namespaces) must not regress.

**Why this priority**: This is the **false-negative guard**.

**Acceptance Scenarios**:

1. **Falso negativo a evitar** — **Given** an HTTP controller `Opscale\Http\UserController` calling `User::find($id)`, **When** PHPStan analyses the file, **Then** the rule reports exactly one error.

---

### Edge Cases

- The curated CRUD list covers: `where*`/`orWhere*`, `orderBy*`/`groupBy*`/`having*`, `join*`, `limit`/`take`/`skip`/`offset`/`forPage`, `select*`/`distinct`/`from*`, `withTrashed`/`onlyTrashed`/`withoutTrashed`, `lockForUpdate`/`sharedLock`, `whereRaw`/`orWhereRaw`/`orHavingRaw`, `get`/`first*`/`find*`/`firstOrNew`/`findOrNew`/`all`/`value`/`pluck`/`sole`, `paginate`/`simplePaginate`/`cursorPaginate`, `count`/`sum`/`avg`/`average`/`min`/`max`/`exists`/`doesntExist`, `create`/`insert*`/`firstOrCreate`, `update*`/`upsert`/`increment`/`decrement`/`save*`, `delete`/`destroy`/`forceDelete`/`restore`.
- Excluded (intentionally not flagged): relationships (`belongsTo`, `hasOne`, `hasMany`, `hasManyThrough`, `belongsToMany`, `morph*`, `with*`, `load*`, `without`, `withOnly`), collection iteration (`chunk*`, `each*`, `lazy*`, `cursor`), model state (`getAttribute`, `setAttribute`, `getAttributes`, `setAttributes`, `getOriginal`, `only`, `except`, `syncOriginal`, `make(Visible|Hidden)`, `append`, `setAppends`, `getVisible`, `getHidden`, `getFillable`, `getGuarded`, `fill`, `forceFill`, `fillable`, `guarded`), utilities (`fresh`, `refresh`, `replicate`, `is`, `isNot`, `getKey*`, `getMorphClass`, `getTable`, `getConnection*`), timestamp utilities (`updateTimestamps`, `usesTimestamps`, `getCreatedAtColumn`, `getUpdatedAtColumn`), events (`observe`, `setObservableEvents`, `getObservableEvents`), serialization and misc (`toArray`, `toJson`, `jsonSerialize`, `toSql`, `dd`, `dump`, `clone`, `newInstance`, `newFromBuilder`, `newQuery`, `newModelQuery`, `wasRecentlyCreated`, `wasChanged`, `isDirty`, `isClean`, `push`, `pushQuietly`), mass-assignment config (`unguard`, `reguard`, `isGuarded`, `isFillable`, `totallyGuarded`, `fillableFromArray`, `trashed`), query-scope mechanics (`withGlobalScope`, `withoutGlobalScope`, `withoutGlobalScopes`, `removedScopes`, `appliedScopes`).
- `touch` / `touchQuietly` are intentionally excluded — they are timestamp utilities. If the user wants to update timestamps via a Service, they call `update(['updated_at' => now()])` or `save()`.

## Requirements *(mandatory)*

- **FR-001**: `getEloquentMethods()` MUST return ONLY the CRUD-scope list defined above.
- **FR-002**: Detection mechanics (direct `Model::method` static call always flags; `self`/`static`/`parent` static call and `$this->method()` only flag inside Eloquent Models) are preserved.
- **FR-003**: Diagnostic identifier `ddd.repositories.eloquentRestriction` MUST be preserved.

## Success Criteria

- **SC-001**: `vendor/bin/pest tests/Rules/EloquentRestrictionTest.php` passes with the five scenarios (1 caso_positivo, 1 caso_negativo, 2 falso_positivo, 1 falso_negativo).
- **SC-002**: `npm test` continues all-green (110 tests).
- **SC-003**: `npm run analyse` reports no errors.

## Assumptions

- Consumer projects have already adopted the previous (broader) rule. This iteration is a relaxation: previously-flagged code (relationships, state, serialization on Models) will stop flagging. Consumer baselines keyed on identifier survive; baselines keyed on specific message lines may shrink.
