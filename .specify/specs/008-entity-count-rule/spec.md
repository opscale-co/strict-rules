# Feature Specification: EntityCountRule — Collector + per-subdomain aggregation

**Feature Branch**: `008-entity-count-rule`
**Created**: 2026-05-01
**Status**: Draft
**Input**: User description: "sube el count por defecto a 25"

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Limit concrete Eloquent entities per subdomain (Priority: P1)

As an architect, I want PHPStan to fail when a single subdomain (a single `\Models` namespace) accumulates more than 25 concrete Eloquent entities. The threshold is a soft guidepost — bigger subdomains should be split.

**Why this priority**: Constitution Article IV — "Subdomains as Packages". A subdomain that grows beyond ~25 entities is a signal it should be decomposed. P1.

**Acceptance Scenarios**:

1. **Caso positivo (true positive)** — **Given** three concrete Eloquent models that share the namespace `\Opscale\Models` AND a configured `maxClasses` of 2, **When** PHPStan analyses all three files, **Then** the rule reports exactly one error stating that the subdomain has 3 entities and exceeds the maximum of 2.

2. **Caso negativo (true negative)** — **Given** two concrete Eloquent models in `\Opscale\Models` AND a configured `maxClasses` of 2, **When** PHPStan analyses both files, **Then** the rule reports zero errors. The threshold is `>`, not `>=`.

---

### User Story 2 — Do not count non-Eloquent classes (Priority: P2)

As a developer, I want non-Eloquent classes (interfaces, traits, enums, abstract classes, plain helpers) to never count toward the entity limit. Subdomain bloat is about entities, not infrastructure.

**Why this priority**: This is the **false-positive** scenario. The previous implementation never fired (its static counter was never incremented), so this case was silent. After the rewrite the rule actually fires — without explicit filtering, an abstract base class or a contract interface would bump the count and force unnecessary splits. Closing this keeps the rule focused on real entities.

**Acceptance Scenarios**:

1. **Falso positivo a evitar** — **Given** four non-Eloquent classes (a regular helper, an empty class, an abstract Eloquent base, an interface) under `\Opscale\Models` AND a configured `maxClasses` of 2, **When** PHPStan analyses them, **Then** the rule reports zero errors.

---

### User Story 3 — Aggregate per subdomain, never lump (Priority: P2)

As an architect, I want each `\Models` namespace to be counted as its own subdomain. A project with `\App\Models` (10 entities) and `\App\Modules\Loans\Models` (8 entities) must not be flagged for "18 entities" — that is the very decomposition the rule is meant to encourage.

**Why this priority**: This is the **false-negative** scenario. The previous implementation, even if its counter had worked, used a single static counter — it would have lumped every analysed Eloquent model into a single bucket. The rewrite groups by file-level namespace so each subdomain is judged independently.

**Acceptance Scenarios**:

1. **Falso negativo a evitar** — **Given** two concrete Eloquent models in `\Opscale\Models` AND one concrete Eloquent model in `\Opscale\Modules\Foo\Models`, AND a configured `maxClasses` of 2, **When** PHPStan analyses all three, **Then** the rule reports zero errors. Each subdomain is at or below its threshold; there is no "total entities = 3" check.

---

### Edge Cases

- A file declaring multiple Eloquent classes in the same namespace — every class counts separately (the collector walks all `Class_` nodes).
- An abstract Eloquent base class — does not count (entities, by intent, are concrete).
- A class in an unrelated namespace (no `\Models` segment) — counts toward whatever namespace it lives in. The intent is "any namespace whose models exceed the limit gets flagged". `BaseNamespaceRule` separately enforces that Eloquent models live in `\Models`.
- Same model FQCN seen multiple times (e.g., due to multi-pass analysis) — counted only once via `array_unique`.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A new collector `EntityCountCollector` MUST emit, for every concrete Eloquent model class declared in any analysed file, a record `[namespace, fqcn, file]` where `namespace` is the file's namespace, `fqcn` is the model's fully-qualified class name, and `file` is the file path.
- **FR-002**: The rule `EntityCountRule` MUST consume the collector's output via `Rule<CollectedDataNode>`. It groups records by `namespace`, deduplicates `fqcn`s within each group, and emits exactly one error per namespace whose unique-fqcn count exceeds `maxClasses`.
- **FR-003**: The rule's constructor default for `maxClasses` MUST be `25`.
- **FR-004**: A class is "concrete Eloquent" iff it has a resolved `namespacedName`, is not anonymous, not an interface, not a trait, not an enum, not abstract, AND its FQCN resolves to a subclass of `Illuminate\Database\Eloquent\Model`.
- **FR-005**: The rule MUST register the collector in `rules.ddd.neon` so consumer projects pick up both pieces automatically.
- **FR-006**: Diagnostic identifier `ddd.subdomains.entityCount` MUST be preserved.
- **FR-007**: The error message MUST name the subdomain namespace, the count, and the threshold.

### Key Entities

- **EntityCountCollector** (new) — `src/Rules/DDD/Subdomains/EntityCountCollector.php`. PHPStan `Collector` over `FileNode` records.
- **EntityCountRule** (rewritten) — `src/Rules/DDD/Subdomains/EntityCountRule.php`. Now `Rule<CollectedDataNode>`.
- **Modules/Foo/Models/FooEntity** fixture (new) — concrete Eloquent model in a second subdomain. Used by the false-negative test.

## Success Criteria *(mandatory)*

- **SC-001**: `vendor/bin/pest tests/Rules/EntityCountTest.php` passes with the four named scenarios.
- **SC-002**: `npm test` continues all-green.
- **SC-003**: Diagnostic identifier remains `ddd.subdomains.entityCount`.
- **SC-004**: Default `maxClasses` is `25`.

## Assumptions

- PHPStan's `Collector` + `Rule<CollectedDataNode>` two-phase pipeline is supported by the version shipped with this repository.
- `RuleTestCase::getCollectors()` is the override point for registering collectors in tests.
- The user's "subdomain" is operationally the file-level namespace string. Two namespaces that differ even by a single segment are separate subdomains.
- Counting only concrete Eloquent models is the right policy — abstract bases and contracts are infrastructure for entities, not entities themselves.
- The collector's emissions are the sole source of truth; the rule does no further reflection. This keeps the rule deterministic over the collected data.
