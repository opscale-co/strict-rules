# Opscale Project Constitution

**Project:** strict-rules
**Project Type:** library
**Module Prefix:** strict-rules
**Tenant Aware:** no
**Created:** 2026-05-01

> This constitution is the architectural DNA of every Opscale project.
> Claude Code MUST read and comply with it before generating any code, spec, plan, or task.
> It is derived from [opscale-co/strict-rules](https://github.com/opscale-co/strict-rules)
> and supersedes all other instructions. Deviations require explicit documentation.

---

## 0. Project Type

**Type: library**

| Type | Description | Example |
|------|-------------|---------|
| `app` | Complete Laravel Nova application containing multiple modules. Deployed via Vapor or similar. | A multi-module SaaS platform |
| `module` | Single bounded-context package within an app. Handles one business subdomain. | `opscale-co/nova-loan-module` |
| `package` | Standalone project with specific functionality. Can have domain, Nova resources, Actions. Published to Packagist. | `nova-api`, `nova-authorization` |
| `library` | Pure utility/infrastructure code. No domain, no Nova, no Actions. | `opscale-co/strict-rules`, `opscale-co/actions` |

### What applies per type

| Skill / Agent | app | module | package | library |
|---------------|:---:|:------:|:-------:|:-------:|
| opscale-init | Yes | Yes | Yes | Yes (simplified) |
| opscale-process | Per module | Yes | -- | -- |
| opscale-dbml | Per module | Yes | -- | -- |
| opscale-bpmn | Per module | Yes | -- | -- |
| opscale-domain | Per module | Yes | Yes (direct) | -- |
| opscale-ui | Per module | Yes | Yes (direct) | -- |
| opscale-logic | Per module | Yes | Yes (direct) | -- |
| opscale-outputs | Per module | Yes | Yes (direct) | -- |
| opscale-debug | Yes | Yes | Yes | Optional |
| opscale-test | Yes | Yes | Yes | Yes (adapted) |
| opscale-release | Yes (deploy-app) | Yes (publish-package) | Yes (publish-package) | Yes (publish-package) |
| opscale-ai | Per module | Yes | -- | -- |

### Library-type sequence (THIS PROJECT)

Because this is a `library`, the development sequence is:

```
1. Write code (PHPStan rules, BaseRule abstract, helpers, fixtures)
2. opscale-test    → configure Pest, PHPStan level 8, Duster, Rector
3. opscale-release → configure Semantic Release, CI/CD, SonarQube
```

Steps 1–7 and 11 of the standard Opscale sequence (spec, DBML, BPMN, domain,
Nova, logic, outputs, AI) do NOT apply to this project. Quality gates still
apply in full: PHPStan level 8, Duster, all tests pass, SonarQube.

This project is a PHPStan extension — its public API is a set of rule classes
registered via `.neon` files. There is no domain model, no aggregate, no Nova
layer, no Action, no Output. The constitution sections that govern those
concerns (II Design Methodology, IV DDD Rules, V Opscale Actions, VI Nova,
VII Outputs, IX Multi-Tenancy) are documented below for reference but are
**not enforced** on the source code of this library. They ARE the rules this
library enforces on consumer projects via PHPStan.

### Package vs Library distinction (for clarity)

- A **package** has a domain layer, may expose Nova resources, and ships
  Opscale Actions. The full spec-driven sequence applies.
- A **library** (this project) is pure infrastructure code — abstractions,
  PHPStan rules, traits, helpers. No domain, no Nova, no Actions.

### Adopting in Existing Projects

This skill is being run on an existing project that already has source code,
tests, and CI. The intent is to adopt Opscale conventions incrementally,
without rewriting working code.

1. **Existing code is the source of truth** — the rules under `src/Rules/`,
   the `BaseRule` abstract, and the test fixtures stay as they are. Do not
   rename, relocate, or refactor them to match a generic library template.
2. **Generation skills must detect existing files** — when a skill (e.g.
   `opscale-test`) targets a path that already exists, it merges additions
   and preserves user-authored content. Conflicts are flagged for review,
   never silently overwritten.
3. **Entry point is `opscale-test`** — the project already has PHPUnit tests
   under `tests/Rules/` and fixtures under `tests/fixtures/`. The next
   useful skill is `opscale-test`, which adapts those tests to the Opscale
   quality stack (Pest preferred, PHPStan level 8, Duster, Rector) without
   losing existing coverage.
4. **`opscale-release` follows** — a `.releaserc.json`, `commitlint`,
   `husky`, `lint-staged`, and a `sonar-project.properties` already exist.
   `opscale-release` will reconcile them with the Opscale release pipeline.

---

## I. Architectural Philosophy

Opscale software is designed in a strict priority order. When there is a
conflict between levels, the higher level always wins.

**Priority 1 — Business (Information Flow)**
The system exists to model how the business works and move information
correctly through it. For this library, "the business" is the set of
architectural rules that consumer projects must follow. The library's job
is to detect violations of those rules accurately — false positives and
false negatives are the equivalent of broken information flow.

**Priority 2 — End Users (Interface)**
The end users of this library are developers running PHPStan in their
Laravel projects. The interface is the error message produced by each rule
and the configuration surface in the `.neon` files. Messages must be
actionable: they should tell the developer what is wrong and what to do
about it, not just that something is wrong.

**Priority 3 — Technical Team (Maintainability)**
The Opscale architecture team maintains this library. SOLID principles,
small focused rule classes, and shared helpers in `BaseRule` keep the
codebase understandable. If a rule grows beyond ~150 lines, it likely
covers more than one architectural concern and should be split.

**The three design patterns that this library enforces in consumers:**
- **DDD** — `src/Rules/DDD/*` — domain modeling with Laravel pragmatism
- **Clean Architecture** — `src/Rules/CLEAN/*` — predictable layer communication
- **SOLID** — `src/Rules/SOLID/*` — maintainable, testable code units
- **Code smells** — `src/Rules/Smells/*` — exception handling, helpers, dummy catches

---

## II. Design Methodology — Spec-Driven Sequence

**Not applicable to this library.** This project has no `spec.md`, no
`data-model.md`, no `process.md`, no `plan.md`, and no `tasks.md`. The
spec-driven sequence governs `app`, `module`, and `package` projects.

For reference, the steps that apply to those project types are documented in
the upstream constitution template. This library skips directly from
`opscale-init` to `opscale-test` to `opscale-release`.

---

## III. Clean Architecture Layers — Reference Only

The directory structure that this library enforces on consumer Laravel
projects is reproduced here for reference. **It is not the structure of
this library itself**, which uses the standard PHPStan extension layout
(`src/Rules/<Domain>/<RuleName>.php`, `tests/Rules/<RuleNameTest>.php`).

```
app|src/
├── Console/Commands/
├── Contracts/
├── Events/
├── Exceptions/
├── Http/{Controllers/API,Middleware,Requests,Resources}
├── Jobs/
├── Listeners/
├── Models/{Enums,Repositories,ValueObjects}
├── Notifications/
├── Nova/{Actions,Cards,Dashboards,Fields,Filters,Lenses,Menus,Metrics,Repeaters}
├── Observers/
├── Policies/
├── Providers/
└── Services/Actions/    ← Opscale Actions (business logic units)
```

| Layer | Classes | Rule |
|-------|---------|------|
| **Representation** | Models | Define what entities ARE — no methods that compute or transform |
| **Communication** | Observers | Emit events when a model changes — no direct calls to other layers |
| **Transformation** | Services, Exceptions | Apply business rules — no HTTP, no Nova, no Eloquent queries |
| **Orchestration** | Jobs, Notifications | Coordinate multi-step processes — no inline business logic |
| **Interaction** | Console, Http, Nova, Policies | Entry points only — delegate to Actions or Repositories |

This library's `src/Rules/CLEAN/*` rules detect violations of the layer
boundaries above in consumer projects.

---

## IV. DDD Rules — Reference Only (this library has no domain)

These rules describe what `src/Rules/DDD/*` enforces on consumer projects:

- **Subdomains as Packages** — each subdomain is an independent Laravel package
- **Aggregates** — only the aggregate root is an entry point for child mutations
- **Entities** — every entity uses a ULID primary key
- **Value Objects** — immutable; never raw JSON in DB; mapped via Laravel casts
- **Repositories** — all Eloquent queries live in Repository classes
- **Domain Logic** — no `if`/loops/computations inside Models, VOs, or Enums
- **Domain Services** — only when an operation spans multiple aggregate roots

This library itself contains no Eloquent models, no aggregates, and no domain
services — these rules apply to the projects this library analyzes.

---

## V. Opscale Actions — Not Applicable

This library has no business logic, therefore no Actions. The
`lorisleiva/laravel-actions` dependency is **not** required.

This library DOES enforce, via `src/Rules/CLEAN/Transformation/*` and
`src/Rules/CLEAN/Orchestration/*`, that consumer projects place business
logic exclusively in Opscale Actions.

---

## VI. Nova Layer Rules — Not Applicable

This library has no Nova resources. It does not depend on Laravel Nova at
runtime. Consumer projects' Nova rules live under `src/Rules/CLEAN/Interaction/*`.

---

## VII. Outputs — Not Applicable

This library produces PHPStan diagnostics, not user-facing output. There are
no Notifications, Jobs, or queued outputs.

---

## VIII. SOLID Rules — APPLY IN FULL TO THIS LIBRARY

These rules apply to the source of this library, not just to consumers:

All PHP files use `declare(strict_types=1)`. PHPStan runs at level 8.

**Single Responsibility**
Every rule class detects exactly one architectural violation. A rule that
checks both ULID enforcement and Repository isolation must be split.
If a class exceeds ~150 lines (`src/Rules/SOLID/SRP/MaxLinesRule.php`), it
likely has more than one responsibility.

**Open/Closed**
Rule classes extend `BaseRule` and override `validate()` to add behavior —
they do not modify `BaseRule` itself. New rules are added as new files
under the appropriate `src/Rules/<Domain>/` directory and registered in
the matching `.neon` file.

**Liskov Substitution**
Subclasses of `BaseRule` must remain interchangeable wherever a `Rule` is
expected. Override `validate()` and `shouldProcess()` only — never weaken
preconditions or strengthen postconditions of inherited methods.

**Interface Segregation**
Each `.neon` rule registration is narrow: a single rule class, configured
with the minimum parameters needed. Avoid catch-all rule classes that take
a flag to switch between unrelated behaviors.

**Dependency Inversion**
Rules depend on `PHPStan\Analyser\Scope`, `PhpParser\Node`, and
`PHPStan\Reflection\ReflectionProvider` — all framework-provided
abstractions. Never instantiate dependencies with `new ClassName()`
inside a rule class body — let PHPStan's container resolve them.

---

## IX. Multi-Tenancy — Not Applicable

`Tenant Aware: no` — this library has no database and no tenant concept.
Consumer projects with tenant-aware modules are governed by their own
constitution; this library's rules do not enforce tenant scoping.

---

## X. Code Quality Gates

No feature branch merges without passing all of the following:

### Apply to this library

1. ✅ PHPStan level 8 — zero errors when analyzing `src/`
2. ✅ Duster lint — PHP clean (no JS/Vue in this library)
3. ✅ All tests pass — Pest + PHPStan `RuleTestCase` for every rule under `src/Rules/*`
4. ✅ SonarQube quality gate — no new critical or blocker issues
5. ✅ Semantic Release commit convention on all commits
6. ✅ Every rule class has a corresponding test file under `tests/Rules/`
7. ✅ Every rule registered in a `rules.*.neon` file is exercised by at least one test

### Do not apply (no domain in this library)

- ~~DBML matches migrations — no DBML, no migrations~~
- ~~Every BPMN action task maps to an implemented Opscale Action — no BPMN~~

---

## XI. Spec-Driven Development Sequence — Not Applicable

This library is exempt from the spec-driven sequence. The applicable
sequence is the library short path:

```
opscale-init     →  scaffold + this constitution         ✅ done
opscale-test     →  Pest + PHPStan level 8 + Duster + Rector configured
opscale-release  →  Semantic Release + CI/CD + SonarQube wired
```

No `.specify/specs/{NNN}/` folders are created for this project.

---

## Governance

- This constitution supersedes all other instructions, templates, and conventions.
- Any deviation requires explicit inline documentation with the business or technical reason.
- Amendments must propagate to all dependent `.specify/templates/` files (when applicable).
- PRs violating any article are blocked until resolved — no exceptions.
- Because this is a `library`, only Articles I, VIII (SOLID), and X (Quality Gates) are
  enforced on this codebase. Articles II, III, IV, V, VI, VII, IX, XI are reference
  material that describes what this library enforces on its consumers.
