# Tasks: 020-disallow-instantiation-rule

## T001 — Add fixture: `tests/fixtures/Mail/SendOrderEmail.php`
Mailable subclass under `Opscale\Mail`. Used by falso_positivo to verify Mailable instantiation is allowed.

## T002 — Add fixture: `tests/fixtures/Services/ServiceInstantiatingModel.php` [P]
Service that instantiates `new User([...])`, `new Product([...])`, and `new SendOrderEmail($payload)`. Used by falso_positivo.

## T003 — Add fixture: `tests/fixtures/Services/MultiInstantiationServices.php` [P]
File with two classes: FirstCleanService (DI only) + SecondViolatingService (instantiates BatchingService via new). Add to autoload-dev.classmap.

## T004 — Modify `src/Rules/SOLID/DIP/DisallowInstantiationRule.php`
Walk classlikes. Add subclass-based allow for Model / Mailable / Notification / JsonResource. Preserve existing allow list.

## T005 — Rewrite `tests/Rules/DisallowInstantiationTest.php`
Four canonical scenarios.

## T006 — Update `src/Rules/SOLID/DIP/documentation.md`
Refresh with subclass-based allowance + multi-class walking + identifier.

## T007 — Verify
`composer dump-autoload`, `npm test` green. Commit `feat(rules):` (additive — allowed list grows).
