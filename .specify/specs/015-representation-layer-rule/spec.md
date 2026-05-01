# Feature Specification: RepresentationLayerRule — allow `Illuminate\Support\Carbon`

**Feature Branch**: `015-representation-layer-rule`
**Created**: 2026-05-01
**Status**: Draft

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Block infrastructure imports in Models (Priority: P1)

As an architect, I want PHPStan to fail when an Eloquent Model imports a class that belongs to a higher layer (Jobs, Controllers, ...) or a facade not allowed in Representation. Models stay declarative — they describe entity shape and relationships, nothing else.

**Acceptance Scenarios**:

1. **Caso positivo (true positive)** — **Given** a Model `Opscale\Models\User` that imports `Opscale\Jobs\CleanOldProducts` (layer 4), `Illuminate\Support\Facades\Storage` (a Transformation-only facade), `Illuminate\Support\Str` (not a Carbon class), and `Illuminate\Http\Request` (Interaction-layer framework), **When** PHPStan analyses the file, **Then** the rule reports four errors.

2. **Caso negativo (true negative)** — **Given** a Model `Opscale\Models\ValidUlidUser` that uses framework traits (HasFactory, Notifiable, HasUlids), extends Authenticatable, and references no other layer, **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 2 — Allow `Illuminate\Support\Carbon` for date typing (Priority: P1)

As a developer writing a Model, my date attributes are typed as `Illuminate\Support\Carbon` (Laravel's Carbon wrapper). The rule should not flag this — it is the canonical date type Laravel ships.

**Why this priority**: This is the **false-positive** scenario. Today's Representation rule allows `Illuminate\Database\` framework imports and `Carbon\` external (the carbon package vendor namespace), but NOT `Illuminate\Support\Carbon` (Laravel's specific wrapper). A Model that types `protected $birthDate: Carbon;` after `use Illuminate\Support\Carbon;` is wrongly flagged.

**Acceptance Scenarios**:

1. **Falso positivo a evitar** — **Given** a Model `Opscale\Models\CarbonTypedModel` that imports `Illuminate\Support\Carbon` and types a property with it, **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 3 — Don't widen the rule beyond what's intended (Priority: P2)

**Acceptance Scenarios**:

1. **Falso negativo a evitar** — **Given** a Model `Opscale\Models\StorageUsingModel` that imports `Illuminate\Support\Facades\Storage` (a Transformation-only facade), **When** PHPStan analyses the file, **Then** the rule reports exactly one error.

---

### Edge Cases

- The rule remains package-agnostic — `Opscale\Models\Foo`, `App\Models\Foo`, etc. all detect as layer 1.
- The trait/Model auto-allow override remains in place: any trait or Eloquent Model class imported by an Eloquent Model file is auto-allowed regardless of namespace.
- Adding `Illuminate\\Support\\Carbon` is class-specific (no trailing slash). It does NOT accidentally permit `Illuminate\Support\Facades\X` or other Support helpers.

## Requirements *(mandatory)*

- **FR-001**: `RepresentationLayerRule` MUST extend `allowedFrameworkImports` with `'Illuminate\\Support\\Carbon'`.
- **FR-002**: No removals from any allowed list. No additions to facades, externals, or other frameworks. Models stay declarative — adding `Log` would invite logging from Models, which the constitution forbids.
- **FR-003**: Diagnostic identifier `clean.layer1.importNotAllowed` MUST remain unchanged.
- **FR-004**: The trait/Model auto-allow override (the existing `isAllowedUse` extension in `RepresentationLayerRule`) MUST remain unchanged.

## Success Criteria

- **SC-001**: `vendor/bin/pest tests/Rules/RepresentationLayerTest.php` passes with the four named scenarios.
- **SC-002**: `npm test` continues all-green.
- **SC-003**: Diagnostic identifier remains `clean.layer1.importNotAllowed`.

## Assumptions

- `Illuminate\Support\Carbon` is a class, not a namespace; the prefix is added without trailing backslash. `str_starts_with` will accept exactly this class. No other Support classes are permitted in Representation — the layer must remain narrow.
- This change is **not breaking** for downstream consumers (allowed lists only grow).
