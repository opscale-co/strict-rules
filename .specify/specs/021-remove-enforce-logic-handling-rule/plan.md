# Implementation Plan: 021-remove-enforce-logic-handling-rule

## Summary

Delete `EnforceLogicHandlingRule` and all its references. No replacement; coverage of dummy `try`/`catch` blocks stays in `NoDummyCatchesRule`.

## Tasks

1. `git rm src/Rules/Smells/EnforceLogicHandlingRule.php`
2. `git rm tests/Rules/EnforceLogicHandlingTest.php`
3. Edit `rules.smells.neon` to remove the EnforceLogicHandlingRule block.
4. Edit `CLAUDE.md` to drop the EnforceLogicHandlingRule line from the Smells directory listing.
5. Run `npm test` (expect 118 → 108 passing, ~10 tests removed).
6. Run `vendor/bin/phpstan analyse --memory-limit=512M` — confirm green.
7. Commit `feat(rules)!: remove EnforceLogicHandlingRule (consolidation)` with BREAKING CHANGE footer.
