# Tasks: 021-remove-enforce-logic-handling-rule

## T001 — Delete the rule
`git rm src/Rules/Smells/EnforceLogicHandlingRule.php`

## T002 — Delete the test
`git rm tests/Rules/EnforceLogicHandlingTest.php`

## T003 — Update rules.smells.neon
Remove the service block for `Opscale\Rules\Smells\EnforceLogicHandlingRule`. Keep `NoDummyCatchesRule` and `HelpersRestrictionRule`.

## T004 — Update CLAUDE.md
Drop the `EnforceLogicHandlingRule   # Exception handling restrictions` line from the Smells directory listing.

## T005 — Verify
`npm test` green. `vendor/bin/phpstan analyse --memory-limit=512M` green.

## T006 — Commit
`feat(rules)!: remove EnforceLogicHandlingRule (consolidation)` with BREAKING CHANGE footer.
