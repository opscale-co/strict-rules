# Feature Specification: Remove EnforceLogicHandlingRule

**Feature Branch**: `021-remove-enforce-logic-handling-rule`
**Created**: 2026-05-01
**Status**: Draft
**Input**: User description: "remueve enforce logic handling"

## User Scenarios *(mandatory)*

### User Story 1 — Drop the rule from the package (Priority: P1)

As an architect, I want to remove `EnforceLogicHandlingRule` from `opscale-co/strict-rules`. The rule's intent overlaps with `NoDummyCatchesRule` (both inspect `try`/`catch` blocks for missing logic) and the maintenance cost is no longer justified.

**Why this priority**: User directive. P1.

**Acceptance Scenarios**:

1. **Given** the current repository, **When** the change lands, **Then**:
   - `src/Rules/Smells/EnforceLogicHandlingRule.php` does not exist.
   - `tests/Rules/EnforceLogicHandlingTest.php` does not exist.
   - `rules.smells.neon` does not register the class.
   - `CLAUDE.md`'s rule directory listing does not mention the rule.
   - `npm test` is green.
   - `vendor/bin/phpstan analyse` is green.

## Requirements *(mandatory)*

- **FR-001**: Delete `src/Rules/Smells/EnforceLogicHandlingRule.php`.
- **FR-002**: Delete `tests/Rules/EnforceLogicHandlingTest.php`.
- **FR-003**: Remove the `Opscale\Rules\Smells\EnforceLogicHandlingRule` service block from `rules.smells.neon`. Keep the other two Smells rules (`NoDummyCatchesRule`, `HelpersRestrictionRule`) registered.
- **FR-004**: Update `CLAUDE.md`'s "Smells" directory listing to drop the `EnforceLogicHandlingRule` line and keep the remaining two.
- **FR-005**: No new replacement rule is introduced. The diagnostic identifier `smells.enforceLogicHandling` is retired; consumer baselines containing it become dead entries that should be removed.

## Success Criteria *(mandatory)*

- **SC-001**: `npm test` continues all-green after removal.
- **SC-002**: `vendor/bin/phpstan analyse` continues all-green.
- **SC-003**: `grep -r "EnforceLogicHandling\|enforceLogicHandling" src tests rules.*.neon CLAUDE.md` returns no matches.

## Assumptions

- The four-scenario test contract does not apply to a deletion feature — there is no rule to test. The single Acceptance Scenario above checks the deletion is complete.
- This is a BREAKING CHANGE for downstream consumers because:
  - Projects that explicitly registered `EnforceLogicHandlingRule` in their own `phpstan.neon` will fail to bootstrap until they remove the registration.
  - Baseline files keyed on `smells.enforceLogicHandling` become stale (PHPStan will warn about unmatched ignored errors).
- Coverage of dummy `try`/`catch` blocks remains via `NoDummyCatchesRule` in the same Smells folder; the consolidation rationale is recorded in this spec.
