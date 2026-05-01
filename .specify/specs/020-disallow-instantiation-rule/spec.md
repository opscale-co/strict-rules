# Feature Specification: DisallowInstantiationRule — recognise Eloquent Models / Mailables / Notifications / Resources, walk multi-class

**Feature Branch**: `020-disallow-instantiation-rule`
**Created**: 2026-05-01
**Status**: Draft
**Input**: User description: "verifica si hay otras clases que sean válidas para instanciación"

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Block service-to-service direct instantiation (Priority: P1)

As an architect, I want PHPStan to fail when a class instantiates another class via `new` instead of receiving the dependency through DI. The DIP exists to keep classes loosely coupled to interfaces / abstractions.

**Why this priority**: Constitution Article VIII (DIP) — "Never instantiate dependencies with `new ClassName()` inside a class body. All dependencies are injected via constructor." P1.

**Acceptance Scenarios**:

1. **Caso positivo (true positive)** — **Given** a class `Opscale\Services\ExternalAPIService` whose `canBatch()` method instantiates `BatchingService` via `new`, **When** PHPStan analyses the file, **Then** the rule reports exactly one error.

2. **Caso negativo (true negative)** — **Given** a class `Opscale\Services\ValidDependencyInjection` that receives all dependencies via constructor, **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 2 — Recognise legitimately-instantiable Laravel patterns (Priority: P1)

As a developer, I expect the rule to leave alone the canonical Laravel patterns where `new` is the right choice:
- **Eloquent Model** instantiation (`new User([...])` to create a domain entity).
- **Mailable** instantiation (`new SendOrderEmail($order)` to dispatch via `Mail::send`).
- **Notification** instantiation (`$user->notify(new InvoicePaid($invoice))`).
- **JsonResource** instantiation (`new UserResource($user)` to format API output).

These libraries are explicitly designed to be instantiated in service code; they are NOT services themselves.

**Why this priority**: This is the **false-positive** scenario. The previous allowed list covered only suffix-based heuristics (`*DTO`, `*Data`, `*Event`, ...) and a small set of named Laravel classes. Any project Model, Mailable, Notification or Resource was wrongly flagged. Closing this aligns the rule with Laravel idiom.

**Acceptance Scenarios**:

1. **Falso positivo a evitar** — **Given** a class `Opscale\Services\ServiceInstantiatingModel` that instantiates `new User([...])` (an Eloquent Model) and `new SendOrderEmail($payload)` (a Mailable subclass), **When** PHPStan analyses the file together with the Mailable fixture, **Then** the rule reports zero errors.

---

### User Story 3 — Walk every classlike in a file (Priority: P2)

As an architect, I want the rule to inspect every classlike declared in the file. Multi-class files are unusual but legal; today's rule only inspects the first class via `getRootNode`, so violating instantiations on subsequent classes slip through.

**Why this priority**: This is the **false-negative** scenario.

**Acceptance Scenarios**:

1. **Falso negativo a evitar** — **Given** a file `Opscale\Services\MultiInstantiationServices` declaring two classes — `FirstCleanService` (no instantiations) and `SecondViolatingService` (instantiates `BatchingService` via `new`) — **When** PHPStan analyses the file, **Then** the rule reports exactly one error attributed to `SecondViolatingService`.

---

### Edge Cases

- The rule continues to skip `__construct` (where instantiations are expected for initialisation).
- `self`, `parent`, `static` instantiation continues to be allowed.
- PHP built-in classes continue to be allowed (Exception, RuntimeException, ...).
- The original suffix list (`DTO`, `ValueObject`, `Value`, `Data`, `Request`, `Response`, `Event`) and named-class list are preserved.
- Mailables, Notifications, JsonResources are recognised by reflecting their inheritance — no name-based heuristic is needed for them.
- Multi-class fixtures need an `autoload-dev.classmap` entry.

## Requirements *(mandatory)*

- **FR-001**: `DisallowInstantiationRule` MUST iterate every `Class_`, `Trait_`, and `Enum_` declared in the file. Today's `getRootNode` is replaced with a multi-classlike walk.
- **FR-002**: `isAllowedInstantiation` MUST also accept any class whose reflection is a subclass of:
  - `Illuminate\Database\Eloquent\Model`,
  - `Illuminate\Mail\Mailable`,
  - `Illuminate\Notifications\Notification`,
  - `Illuminate\Http\Resources\Json\JsonResource`.
- **FR-003**: All previously-allowed names and suffixes are preserved. The change is purely additive.
- **FR-004**: Diagnostic identifier `solid.dip.disallowInstantiation` MUST be preserved.

## Success Criteria

- **SC-001**: `vendor/bin/pest tests/Rules/DisallowInstantiationTest.php` passes with the four named scenarios.
- **SC-002**: `npm test` continues all-green.
- **SC-003**: Diagnostic identifier remains `solid.dip.disallowInstantiation`.

## Assumptions

- A consumer project's Model classes always extend `Illuminate\Database\Eloquent\Model`. The reflection check captures custom Authenticatable subclasses (which extend Model transitively) as well.
- The Mailable, Notification and JsonResource base classes are part of Laravel's framework — projects that add them to their composer.json have them resolvable by PHPStan's reflection provider.
- This feature is **not breaking** in the strict semantic-release sense (allowed lists only grow). However, the existing `detects_multiple_instantiation_violations` test asserts errors on `new User()` lines that disappear after this change; the test is restructured into the canonical four scenarios.
