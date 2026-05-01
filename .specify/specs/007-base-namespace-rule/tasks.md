# Tasks: 007-base-namespace-rule

## T001 — Add fixture: `tests/fixtures/Domain/JustAHelper.php`
Plain non-Eloquent class under `\Opscale\Domain`. Used to verify the rule does not over-flag non-Models.

## T002 — Add fixture: `tests/fixtures/Domain/MultiClassFile.php` [P]
Single file with two class declarations under `\Opscale\Domain`. The first class is non-Eloquent; the second class extends `Illuminate\Database\Eloquent\Model`. Used to verify the rule walks every class.

## T003 — Rewrite `src/Rules/DDD/Subdomains/BaseNamespaceRule.php`
- Extend `BaseRule` (not `DomainRule`).
- New `shouldProcess`: return true for any `FileNode`.
- New `validate`: iterate `getClassNodes`, per-class skip non-Eloquent, namespace-check, emit one error per non-conforming class at the class's own line.
- Refresh the error message wording.
- `declare(strict_types=1)`.

## T004 — Rewrite `tests/Rules/BaseNamespaceTest.php`
Four `#[Test]` methods covering the scenarios above.

## T005 — Update `src/Rules/DDD/Subdomains/documentation.md`
Refresh the `BaseNamespaceRule` block: add identifier, reword condition, mention multi-class walking.

## T006 — Verify
`npm test` green. Commit `feat(rules)!:` with `BREAKING CHANGE:` footer (per-class line + multi-class walking change consumer baselines).
