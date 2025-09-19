# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is **strict-rules**, a PHPStan extension by Opscale that enforces architectural guidelines for Laravel projects. It implements three architectural approaches:
- **DDD (Domain-Driven Design)** - Domain modeling with Laravel pragmatism
- **Clean Architecture** - Layered separation of concerns
- **SOLID Principles** - Code smell prevention

The package provides PHPStan rules that analyze Laravel codebases to enforce these architectural patterns.

## Commands

### Testing
```bash
npm test                                    # Run full PHPUnit test suite
vendor/bin/phpunit tests/Rules/SpecificTest.php  # Run a single test file
vendor/bin/phpunit --filter "test_method_name"   # Run specific test method
```

### Linting & Code Quality
```bash
npm run lint                               # Run Duster linting (Laravel-specific)
npm run fix                                # Auto-fix code style issues
vendor/bin/phpstan analyze                 # Run PHPStan architectural analysis
vendor/bin/phpstan analyze --error-format=table  # Table format for better readability
```

### Development Workflow
```bash
git add .                                  # Stage changes
git commit -m "type(scope): message"      # Conventional commit (enforced by commitlint)
# Types: feat, fix, docs, style, refactor, test, chore
```

## High-Level Architecture

### Rule Implementation Pattern
All rules extend `BaseRule` (`src/Rules/BaseRule.php`) which provides:
- `shouldProcess()` - Determines if a file should be analyzed
- `validate()` - Performs the actual validation (must be implemented by each rule)
- Helper methods: `getRootNode()`, `getClassReflection()`, `getMethodNodes()`, etc.

Rules use PHPStan's AST (Abstract Syntax Tree) to analyze code structure without executing it.

### Directory Organization
```
src/Rules/
├── DDD/           # Domain-Driven Design rules
│   ├── Aggregates/       # Consistency boundary enforcement
│   ├── Domain/           # Business logic purity
│   ├── DomainServices/   # Complex operation coordination
│   ├── Entities/         # ULID enforcement for identities
│   ├── Repositories/     # Eloquent isolation patterns
│   ├── Subdomains/       # Package modularization
│   └── ValueObjects/     # Immutability enforcement
├── CLEAN/         # Clean Architecture layers
│   ├── Communication/    # Event-based notifications
│   ├── Interaction/      # External interfaces (Controllers, Jobs)
│   ├── Orchestration/    # Workflow coordination (Actions, Jobs)
│   ├── Representation/   # Domain entities (Models)
│   └── Transformation/   # Business rules (Services, Actions)
├── SOLID/         # SOLID principle enforcement
│   ├── SRP/ (MaxLinesRule)           # Single Responsibility
│   ├── OCP/ (ConditionalOverrideRule) # Open/Closed
│   ├── LSP/ (ParentCallRule)          # Liskov Substitution
│   ├── ISP/ (EnforceImplementationRule) # Interface Segregation
│   └── DIP/ (DisallowInstantiationRule) # Dependency Inversion
└── Smells/        # Code smell detection
    ├── EnforceLogicHandlingRule   # Exception handling restrictions
    ├── NoDummyCatchesRule         # Empty catch block detection
    └── HelpersRestrictionRule     # Helper function usage prevention
```

### Rule Configuration Files
The package provides modular `.neon` files for PHPStan:
- `rules.ddd.neon` - All DDD-related rules
- `rules.clean.neon` - Clean Architecture layer rules
- `rules.solid.neon` - SOLID principle rules
- `rules.smells.neon` - Code smell rules

### Testing Strategy
- Each rule has a corresponding test in `tests/Rules/`
- Test fixtures in `tests/fixtures/` simulate various code patterns
- Tests use PHPStan's `RuleTestCase` for AST-based testing
- One test file per rule (consolidated tests for related scenarios)

### Common Development Patterns

When creating a new rule:
1. Extend `BaseRule` in appropriate directory
2. Implement `validate(Node $node): array` method
3. Use `shouldProcess()` to filter which files to analyze
4. Return array of `RuleErrorBuilder` messages for violations
5. Create corresponding test extending `RuleTestCase`
6. Add test fixtures demonstrating violations and valid code
7. Register rule in appropriate `.neon` configuration file

When fixing test failures:
- Check expected vs actual error messages (often differ in wording)
- Verify line numbers match in test assertions
- Use `vendor/bin/phpunit --filter` to isolate specific tests
- Remember rules analyze AST, not runtime behavior

### Important Notes

- All rules assume `declare(strict_types=1)` in analyzed files
- Rules work with PHPStan level 5+ (recommended level 8)
- Laravel 11 and PHP 8.2+ are required
- Rules are designed to be pragmatic, not dogmatic
- Tests may have XDebug config warnings (can be ignored)