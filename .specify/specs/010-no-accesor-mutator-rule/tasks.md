# Tasks: 010-no-accesor-mutator-rule

## T001 — Add fixture: `tests/fixtures/Models/InfrastructureOverrideModel.php`
Eloquent model under `\Opscale\Models` that overrides Eloquent's own `getAttribute($key)` and `setAttribute($key, $value)` framework methods. Contains no custom `<Name>Attribute` mutators/accessors.

## T002 — Add fixture: `tests/fixtures/Models/MultiModelWithMutator.php` [P]
Single file with two Eloquent models under `\Opscale\Models`. The first (`FirstCleanModel`) is clean. The second (`SecondModelWithMutator`) defines `getNameAttribute`. Add to `autoload-dev.classmap`.

## T003 — Rewrite `src/Rules/DDD/ValueObjects/NoAccesorMutatorRule.php`
Extend `BaseRule`. New `shouldProcess` (FileNode + namespace gate). New `validate` walks `getClassNodes`, skips non-Eloquent, walks methods. Tighten regex (drop string-prefix/suffix fallback). Use exact FQCN match for `Illuminate\Database\Eloquent\Casts\Attribute`. `declare(strict_types=1)`.

## T004 — Rewrite `tests/Rules/NoAccesorMutatorTest.php`
Four named scenarios.

## T005 — Update `src/Rules/DDD/ValueObjects/documentation.md`
Refresh the `NoAccesorMutatorRule` block: identifier, exact-FQCN check, multi-class walking, framework-override exclusion.

## T006 — Verify
`composer dump-autoload`, `npm test` green. Commit `feat(rules)!:` with `BREAKING CHANGE:` footer.
