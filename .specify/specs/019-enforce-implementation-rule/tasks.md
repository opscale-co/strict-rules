# Tasks: 019-enforce-implementation-rule

## T001 — Add fixture: `tests/fixtures/Services/IntentionalShortMethodService.php`
Class implementing `Batchable` whose every interface method has a single substantive statement (no throw, no default-value return). Used by falso_positivo.

## T002 — Add fixture: `tests/fixtures/Contracts/InheritedContract.php` [P]
Interface with a single public method.

## T003 — Add fixture: `tests/fixtures/Models/ConcreteParentImplementer.php` [P]
Class implementing `InheritedContract` with a substantive body for the contract method.

## T004 — Add fixture: `tests/fixtures/Models/StubChildOverrider.php` [P]
Child class extending `ConcreteParentImplementer` that overrides the inherited contract method with a single-throw stub body. The class does NOT directly declare `implements InheritedContract`.

## T005 — Modify `src/Rules/SOLID/ISP/EnforceImplementationRule.php`
Walk classlikes (Class_, Trait_; skip Enum_). Resolve interface methods via `ClassReflection::getInterfaces()`.

## T006 — Rewrite `tests/Rules/EnforceImplementationTest.php`
Four canonical scenarios.

## T007 — Update `src/Rules/SOLID/ISP/documentation.md`
Refresh with multi-class walking + transitive interface detection + identifier.

## T008 — Verify
`composer dump-autoload`, `npm test` green. Commit `feat(rules)!:` (transitive detection adds new positive cases for inherited interfaces).
