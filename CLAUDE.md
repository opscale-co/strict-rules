# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is **strict-rules**, a PHPStan extension by Opscale that enforces architectural guidelines for Laravel projects. It implements three architectural approaches:
- **DDD (Domain-Driven Design)** — Domain modeling with Laravel pragmatism
- **Clean Architecture** — Layered separation of concerns
- **SOLID Principles** — Code smell prevention

The package provides PHPStan rules that analyze Laravel codebases to enforce these architectural patterns. The rules are package-agnostic — the regex (`^(\w+)(\\\w+)*(\\Folder\\)`) accepts any first-segment root, so consumer projects can use `App\`, `Opscale\`, or any custom namespace.

## Commands

### Testing

The package uses Pest 3 on top of PHPStan's `RuleTestCase`.

```bash
npm test                                     # Full test suite (Pest with memory_limit=512M)
npm run test:coverage                        # With XDebug coverage
./vendor/bin/pest tests/Rules/SpecificTest.php  # Single test file
./vendor/bin/pest --filter "test_method_name"   # Single test method (Pest accepts the PHPUnit --filter flag)
```

### Linting & Code Quality

```bash
npm run lint                                  # Duster (TLint + CodeSniffer + CS Fixer + Pint + PHPStan)
npm run fix                                   # Auto-fix style
npm run analyse                               # PHPStan only (vendor/bin/phpstan analyse --memory-limit=512M)
npm run check                                 # fix → refactor → lint → analyse → test
vendor/bin/phpstan analyse --memory-limit=512M --error-format=table   # Readable PHPStan output
```

The `--memory-limit=512M` flag is mandatory: with the four strict-rules sets active, the default 128M is insufficient.

### Development Workflow

```bash
git add .                                  # Stage changes
git commit -m "type(scope): message"      # Conventional commit (enforced by commitlint)
# Types: feat, fix, docs, style, refactor, test, chore, perf, build, ci, revert
# Use feat(rules)!: ... for breaking rule changes (see Per-rule update workflow below)
```

## High-Level Architecture

### Rule implementation pattern

All rules extend `BaseRule` (`src/Rules/BaseRule.php`) which provides:
- `shouldProcess(Node, Scope): bool` — gate to short-circuit before validate.
- `validate(Node): array` — abstract; each rule implements its own logic.
- Helper methods: `getRootNode()`, `getClassReflection()`, `getMethodNodes()`, `getInterfaceNodes()`, `getNamespace()`, `getNamespaceNode()`, `getUseStatements()`, `getParentNode()`, `getTraitNodes()`, `isInNamespaces()`, `getASTForClass()`, `processNode()`.

Rules use PHPStan's AST (Abstract Syntax Tree) plus reflection to analyze code structure without executing it. Most rules are `Rule<FileNode>` — one rule invocation per file. The `EntityCountRule` is a `Rule<CollectedDataNode>` paired with a `Collector<FileNode>` (`src/Rules/DDD/Domain/Helpers/EntityCountCollector.php`) for project-wide aggregation.

### Multi-class file walking

Most rules iterate every classlike declared in the file (Class_, Trait_, Enum_) rather than only the first via `getRootNode`. The convention is:

```php
private function getClassLikeNodes(FileNode $fileNode): array
{
    $nodes = [];
    foreach ($fileNode->getNodes() as $stmt) {
        if (!$stmt instanceof Namespace_) continue;
        foreach ($stmt->stmts as $inner) {
            if ($inner instanceof Class_ || $inner instanceof Trait_ || $inner instanceof Enum_) {
                $nodes[] = $inner;
            }
        }
    }
    return $nodes;
}
```

When writing fixtures with multiple classes per file, register them in `composer.json` `autoload-dev.classmap` — PSR-4 cannot autoload multi-class files. The list of multi-class fixtures is in `composer.json`.

### Transitive reflection vs AST direct

When a rule needs to know "does this class implement interface X / extend class Y / use trait Z", prefer PHPStan's `ClassReflection` over the AST:

| Question | Use |
|---|---|
| Direct + inherited interfaces | `ClassReflection::getInterfaces()` |
| Inheritance chain (parents) | `ClassReflection::getParents()` |
| Direct traits per class | `ClassReflection::getNativeReflection()->getTraitNames()` |
| Is subclass of FQCN | `ClassReflection::isSubclassOf(SomeClass::class)` |

The AST `getInterfaceNodes()` and `getTraitNodes()` only return the literal `implements` / `use` clauses on the leaf class — they miss inherited and transitively-extended contracts.

### Directory organization

```
src/Rules/
├── BaseRule.php                       # Abstract parent for every rule
├── DDD/                               # Domain-Driven Design rules
│   ├── Aggregates/
│   │   ├── ModelValidationRule.php          # Validatable trait enforcement
│   │   └── ParentChildTransactionRule.php   # save() on belongsTo blocked
│   ├── Domain/
│   │   ├── NoStatementsLogicRule.php        # if/match/loops in Models blocked
│   │   └── Helpers/
│   │       └── EntityCountCollector.php     # Collector for EntityCountRule
│   ├── DomainServices/
│   │   └── ComplexLogicRule.php             # > 2 distinct Models outside \Services\* blocked
│   ├── Entities/
│   │   └── EnforceUlidsRule.php             # HasUlids trait enforcement
│   ├── Repositories/
│   │   └── EloquentRestrictionRule.php      # Eloquent calls only in Repositories or Services
│   ├── Subdomains/
│   │   ├── BaseNamespaceRule.php            # Models flat under \Models segment
│   │   └── EntityCountRule.php              # Subdomain entity cap (default 25)
│   └── ValueObjects/
│       ├── EnforceCastRule.php              # CastsAttributes implementation enforcement
│       └── NoAccesorMutatorRule.php         # No custom *Attribute methods on Models
├── CLEAN/                             # Clean Architecture layer rules
│   ├── CleanRule.php                        # Abstract parent for every layer rule
│   ├── Communication/
│   │   └── CommunicationLayerRule.php       # \Observers\* layer 2
│   ├── Interaction/
│   │   └── InteractionLayerRule.php         # \Console\, \Http\, \Nova\, \Policies\ layer 5
│   ├── Orchestration/
│   │   └── OrchestrationLayerRule.php       # \Jobs\, \Notifications\ layer 4
│   ├── Representation/
│   │   └── RepresentationLayerRule.php      # \Models\* layer 1
│   └── Transformation/
│       └── TransformationLayerRule.php      # \Services\, \Exceptions\, \Contracts\ layer 3
├── SOLID/                             # SOLID principle enforcement
│   ├── SRP/MaxLinesRule.php                 # Class line cap
│   ├── OCP/ConditionalOverrideRule.php      # final / Override / abstract enforcement
│   ├── LSP/ParentCallRule.php               # parent:: required for concrete overrides
│   ├── ISP/EnforceImplementationRule.php    # No interface stubs
│   └── DIP/DisallowInstantiationRule.php    # No direct `new` for service classes
└── Smells/                            # Code smell detection
    ├── NoDummyCatchesRule.php               # No empty / return-only / bare-throw catches
    └── HelpersRestrictionRule.php           # No Laravel global helper functions
```

### Rule configuration files

The package provides modular `.neon` files for PHPStan:
- `rules.ddd.neon` — DDD rules (10 rules + EntityCountCollector)
- `rules.clean.neon` — CLEAN layer rules (5)
- `rules.solid.neon` — SOLID principle rules (5)
- `rules.smells.neon` — Code smell rules (2)

Consumer projects pick up the bundle by including these files in their own `phpstan.neon`.

### Diagnostic identifiers

Every rule emits errors with a stable identifier (e.g., `ddd.aggregates.modelValidation`, `solid.srp.maxLines`). Identifiers are preserved across breaking changes so consumer baselines keyed on identifier (not on message text or line number) survive upgrades. The full table is in `README.md`.

### Testing strategy

- One test file per rule under `tests/Rules/<Rule>Test.php`.
- Each test file covers four canonical scenarios: `caso_positivo`, `caso_negativo`, `falso_positivo_*`, `falso_negativo_*` (the workflow contract — see "Per-rule update workflow" below).
- Test fixtures in `tests/fixtures/` simulate violations and valid code.
- Tests use PHPStan's `RuleTestCase`. Pest is the runner — its functional API is not used here because RuleTestCase requires class-based inheritance.
- `RuleTestCase::getCollectors()` is the override point for `Rule<CollectedDataNode>` tests (see `tests/Rules/EntityCountTest.php`).

## Common Development Patterns

### Per-rule update workflow

Every rule update follows the same four-step workflow inside its own spec-kit feature folder under `.specify/specs/NNN-rule-slug/` (one feature per rule, monotonically numbered):

1. **Modify the rule** — `src/Rules/<Domain>/<RuleName>.php`. Tighten matching, walk inheritance where relevant, preserve the diagnostic identifier so consumer baselines do not drift.
2. **Modify / add tests** — `tests/Rules/<RuleName>Test.php`. Each rule's test file MUST cover four named scenarios with Spanish-named methods following the pattern `caso_positivo_*`, `caso_negativo_*`, `falso_positivo_*`, `falso_negativo_*`. Add fixtures under `tests/fixtures/...` when needed.
3. **Modify documentation if necessary** — `src/Rules/<Domain>/documentation.md` (per-domain doc) and `README.md`'s rule catalogue. Skip only when nothing user-visible changed.
4. **Commit** — Conventional Commits per `commitlint.config.mjs`. One commit per rule feature. If consumer behaviour changes (stricter matching, message text change, dependency swap), use `feat(rules)!:` plus a `BREAKING CHANGE:` footer so semantic-release bumps major. Husky pre-commit (lint-staged → Duster) and commit-msg (commitlint) hooks must pass without `--no-verify`. If Pint auto-fixes files in the hook, re-stage and commit.

### Creating a new rule

1. Extend `BaseRule` in the appropriate directory.
2. Implement `validate(Node $node): array`.
3. Use `shouldProcess(Node, Scope)` to filter which files to analyze.
4. Walk every classlike via `getClassLikeNodes` (multi-class file support) — see existing rules for the pattern.
5. Return an array of `RuleErrorBuilder` errors with a stable identifier (`<domain>.<concept>.<ruleName>`).
6. Create a test file under `tests/Rules/` with the four canonical scenarios.
7. Add fixtures under `tests/fixtures/<area>/` demonstrating violation and valid code.
8. Multi-class fixture? Add it to `composer.json` `autoload-dev.classmap`.
9. Register the rule in the appropriate `rules.*.neon`.
10. Update `README.md`'s rule catalogue and `src/Rules/<Domain>/documentation.md` if needed.

### Fixing test failures

- Check expected vs actual error messages (often differ in wording).
- Verify line numbers match in test assertions — multi-class fixtures and class-level line counters are sensitive to file layout.
- Use `./vendor/bin/pest --filter` to isolate specific tests.
- Remember rules analyze AST + reflection, not runtime behavior.

### Important notes

- All rules assume `declare(strict_types=1)` in analyzed files.
- The package is tested at PHPStan level 5 (working target: level 8). Bumping to level 8 surfaces ~75 `missingType.generics` findings in the test suite that need a separate cleanup feature.
- Laravel 11 and PHP 8.2+ are required.
- Rules are designed to be pragmatic, not dogmatic. Most rules accept constructor arguments to soften thresholds (`maxLines`, `maxClasses`) or extend allow-lists (`additionalAllowedClasses`).
- Tests may have XDebug config warnings (can be ignored).

<!-- SPECKIT START -->
For additional context about technologies to be used, project structure,
shell commands, and other important information, read the current plan
<!-- SPECKIT END -->
