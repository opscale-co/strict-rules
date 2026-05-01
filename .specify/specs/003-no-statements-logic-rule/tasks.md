# Tasks: 003-no-statements-logic-rule

## T001 — Add fixture: `tests/fixtures/Models/DeclarativeAccessorModel.php`

Eloquent model whose `name()` accessor returns `Attribute::make(get: function (...) { if (...) { ... } })`. The model has no other control flow. Used as the false-positive scenario.

## T002 — Add fixture: `tests/fixtures/Models/MatchUsingModel.php` [P]

Eloquent model whose `status()` method body returns a `match($this->state) { ... }` expression. Used as the false-negative scenario.

## T003 — Modify `src/Rules/DDD/Domain/NoStatementsLogicRule.php`

- Add `Match_` to the recognised statement types with key `match`.
- Replace per-type `NodeFinder::findInstanceOf` calls with a single traversal using a private `NodeVisitor` that:
  - Returns `NodeVisitor::DONT_TRAVERSE_CHILDREN` on `Closure` and `ArrowFunction`.
  - Collects matching nodes into a typed bucket per recognised statement.
- Preserve the diagnostic identifier and the error message format.

## T004 — Rewrite `tests/Rules/NoStatementsLogicTest.php`

Four `#[Test]` methods covering the scenarios in the spec.

## T005 — Update `src/Rules/DDD/Domain/documentation.md`

- Mention `match` in the prohibited list.
- Mention that closures (`Attribute::make(get: function ...)`) are exempt and why.
- Add the diagnostic identifier row.

## T006 — Verify

- `npm test` green (93 → 95 expected, +2 net).
- Commit `feat(rules)!: ...` with `BREAKING CHANGE:` footer about the new `match` detection.
