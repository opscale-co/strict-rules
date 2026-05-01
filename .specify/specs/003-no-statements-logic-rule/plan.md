# Implementation Plan: 003-no-statements-logic-rule

**Branch**: `003-no-statements-logic-rule`
**Spec**: `.specify/specs/003-no-statements-logic-rule/spec.md`
**Date**: 2026-05-01

## Summary

Add `match` to the set of control-flow statements `NoStatementsLogicRule` detects in Eloquent models, and stop descending into `Closure` and `ArrowFunction` subtrees so encapsulated control flow inside Laravel idioms (`Attribute::make(get: function (...) { if ... })`) is no longer mis-flagged. Add four named test scenarios.

## Technical Context

- **Language**: PHP 8.2
- **Static analyser**: PHPStan + Larastan, level 5 (target level 8)
- **Test runner**: Pest 3.8 over PHPStan's `RuleTestCase`
- **AST library**: `nikic/php-parser` shipped with PHPStan
- **Diagnostic identifier**: `ddd.domain.noStatementsLogic` (preserved)
- **Traversal**: replace `NodeFinder::findInstanceOf` with a custom `NodeVisitor` that returns `NodeVisitor::DONT_TRAVERSE_CHILDREN` for `Closure` and `ArrowFunction` and collects the target statement nodes otherwise.

## Constitution Check

- ✅ Article I — closing the false negative tightens information flow; closing the false positive prevents architects from disabling the rule.
- ✅ Article IV — Models stay declarative, but closures used as Laravel callbacks are explicitly allowed.
- ✅ Article VIII (SOLID) — the rule retains a single responsibility; helpers are private.
- ✅ Article X.6/7 — every behaviour variant has a named test scenario.

## Project Structure

```
src/Rules/DDD/Domain/
├── NoStatementsLogicRule.php   ← MODIFIED — visitor traversal + Match_
└── documentation.md             ← MODIFIED — match included, closures excluded

tests/fixtures/Models/
├── User.php                              ← unchanged (positive)
├── ValidUlidUser.php                     ← unchanged (negative)
├── DeclarativeAccessorModel.php          ← NEW — if inside Attribute::make closure
└── MatchUsingModel.php                   ← NEW — match in method body

tests/Rules/
└── NoStatementsLogicTest.php   ← MODIFIED — 4 scenarios
```

## Phase 1 — Design

### Rule logic (after change)

```
validate($node):
  if !isEloquentModel($node): return []
  $classNode = getRootNode($node)
  for each $method on $classNode (skip __construct):
    $found = collectControlFlowSkippingClosures($method->stmts)
    for each ($statement, $statements) in $found:
      for each occurrence:
        emit error "Method ... contains a \"$statement\" statement..."

collectControlFlowSkippingClosures(stmts):
  traverser = new NodeTraverser
  visitor = anonymous class extends NodeVisitorAbstract:
    enterNode($node):
      if $node instanceof Closure || ArrowFunction:
        return NodeVisitor::DONT_TRAVERSE_CHILDREN
      foreach (TARGET_TYPES as $key => $class):
        if $node instanceof $class:
          $this->found[$key][] = $node
      return null
  traverser.addVisitor(visitor)
  traverser.traverse(stmts)
  return visitor.found
```

### TARGET_TYPES constant

```php
[
    'for'     => For_::class,
    'foreach' => Foreach_::class,
    'while'   => While_::class,
    'dowhile' => Do_::class,
    'switch'  => Switch_::class,
    'match'   => Match_::class,
    'if'      => If_::class,
]
```

### Test scenarios

| # | Method name | Fixture | Expected errors |
|---|---|---|---|
| 1 | `caso_positivo_if_directo` | `Models/User.php` | 1 — line 53 (if in getEmail) |
| 2 | `caso_negativo_modelo_declarativo` | `Models/ValidUlidUser.php` | 0 |
| 3 | `falso_positivo_if_dentro_de_closure` | `Models/DeclarativeAccessorModel.php` | 0 (closure body skipped) |
| 4 | `falso_negativo_match_en_metodo` | `Models/MatchUsingModel.php` | 1 with statement-name `match` |

## Phase 2 — Tasks (see `tasks.md`)

## Risks & Mitigations

| Risk | Mitigation |
|---|---|
| The visitor pattern is more verbose than `NodeFinder::findInstanceOf` | Keeps a private helper inside the rule class — same number of lines, but correct. |
| `Match_` was added in nikic/php-parser 4.x; the project uses PhpVersion 8.2 | Already supported by the parser shipped with PHPStan in this repo. |
| Skipping closures changes existing behaviour for consumer projects | Explicitly documented in BREAKING CHANGE — codebases that depended on closure-bodies being scanned will see fewer errors (a loosening, not a breakage of pre-condition). The breaking part is the new `match` detection. |

## Out of Scope

- `try`/`catch`, ternary, null-coalescing, spaceship operators — separate feature.
- Detecting nested closures' depth or arrow-function expressions whose body is itself a control-flow expression — the visitor stops at the closure boundary; nothing inside is inspected.
