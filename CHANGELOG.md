## [1.2.0](https://github.com/opscale-co/strict-rules/compare/v1.1.3...v1.2.0) (2026-05-01)

### ⚠ BREAKING CHANGES

* **rules:** helper usage on later classes in multi-class files
now gets flagged. Diagnostic identifier
(smells.helpersRestriction.helper) unchanged.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
* **rules:** dummy catches on later classes in multi-class files
now get flagged. Wrapping throws stop being flagged. Diagnostic
identifier (smells.noDummyCatches) unchanged.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
* **rules:** EnforceLogicHandlingRule and its diagnostic identifier
smells.enforceLogicHandling no longer exist. Consumer projects that
explicitly registered the class in their phpstan.neon will fail to
bootstrap until they remove the registration. Baseline files keyed on
smells.enforceLogicHandling become stale and should be cleaned up. The
rule is not replaced — review your codebase for try/catch blocks that
were caught only by this rule and rely on NoDummyCatchesRule for
ongoing protection.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
* **rules:** stubs of interface methods inherited through a parent
class are now flagged. Multi-class files are fully covered. Diagnostic
identifier (solid.isp.enforceImplementation) unchanged.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
* **rules:** previously-hidden methods on later classes in
multi-class files now get flagged. Diagnostic identifier
(solid.lsp.parentCall) unchanged.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
* **rules:** previously-hidden methods on later classes in
multi-class files get flagged. Magic methods (__-prefixed) stop being
flagged. Diagnostic identifier (solid.ocp.conditionalOverride)
unchanged.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
* **rules:** MaxLinesRule's line count is now per class, not per
file. Consumer baselines pinned by exact line counts will need
regeneration: a file with N use statements above a class previously
counted those N lines toward the cap and now does not, so the
reported "X lines" number drops accordingly. Multi-class files also
now produce one error per oversized class instead of a single error
for the first one. Baselines keyed on the diagnostic identifier
(solid.srp.maxLines) remain valid.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
* **rules:** NoAccesorMutatorRule's detection is tighter on three
fronts: (a) the redundant `set+Attribute` / `get+Attribute` substring
fallback is removed, so custom methods like `setAttribute()` and
`getAttribute()` (Eloquent infrastructure overrides) stop being
flagged; (b) the Attribute-method check now requires the exact Laravel
FQCN, so custom `\Attribute` classes in other namespaces stop being
flagged; (c) multi-class files are now fully walked, so previously-
hidden Eloquent classes in subsequent declarations now get flagged.
The diagnostic identifier (ddd.valueObjects.noAccesorMutator) is
unchanged.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
* **rules:** EnforceCastRule's reported line moves from the class
declaration line of the first class in the file to each class's own
line. The error message is reworded. Two new behaviours: classes whose
contract is inherited stop being falsely flagged, and later classes in
multi-class files are now flagged. Baselines pinned by line number or
message text need regeneration; baselines keyed on the diagnostic
identifier (ddd.valueObjects.enforceCast) remain valid.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
* **rules:** EntityCountRule actually fires now. Consumer projects
that have subdomains with more than 25 concrete Eloquent models will
see new errors. Lower the default via NEON `arguments` if a higher
limit is intentional. The rule's NEON wiring also requires the new
EntityCountCollector to be registered with the `phpstan.collector`
tag — `rules.ddd.neon` does this by default.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
* **rules:** BaseNamespaceRule's reported line moves from the
namespace-declaration line to the class-declaration line, so consumer
baselines pinned by line number need regeneration. The rule also now
flags previously-missed Eloquent classes that sat behind a
non-Eloquent first class in the same file — codebases that had such
multi-class files will see new errors. Baselines keyed on the
diagnostic identifier (ddd.subdomains.baseNamespace) remain valid.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
* **rules:** EloquentRestrictionRule now applies to every class
outside \Models\Repositories\* and \Services\*, not only to classes
under \Models\*. Consumer projects with Eloquent calls in Controllers,
Jobs, Listeners, Observers, Nova classes or Console commands will see
new errors — the fix is to move those calls into a Repository trait or
a Service / Action class. The error message text is also updated to
mention both allowed namespaces; baselines that match by message text
need regeneration. Baselines keyed on the diagnostic identifier
(ddd.repositories.eloquentRestriction) remain valid.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
* **rules:** EnforceUlidsRule now flags classes that declare
HasUlids alongside property overrides that neutralise it
(public $incrementing = true; protected $keyType = 'int';). Consumer
projects that rely on these overrides during partial migrations will
see new errors — the fix is to remove the overrides once the migration
is complete, or to remove the trait if the class is intentionally
non-ULID. Concurrently, models with HasUlids in non-first TraitUse
statements and models inheriting HasUlids from a parent class will
stop being falsely flagged. The error messages now have two variants;
baselines that match by message text need regeneration. Baselines
keyed on the diagnostic identifier (ddd.entities.enforceUlids) remain
valid.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
* **rules:** ComplexLogicRule is materially different from before:
(a) it now applies to every class outside \Services\*, not only to
classes under \Models\*; (b) the threshold is "> 2 distinct models"
rather than "> 1 imported model"; (c) the metric counts operations
(StaticCall, New_, ->save() on typed param) rather than use imports.
Consumers will see new errors on Controllers, Jobs, Listeners,
Observers, Nova Actions and similar classes that orchestrate three or
more entities — the fix is to extract the orchestration to an Opscale
Action under \Services\Actions\*. Concurrently, false positives on
Form Requests / Nova Resources / DTOs that referenced many models via
type hints or ::class disappear. The error message text is new;
baselines that match by message will need regeneration. Baselines
keyed on the diagnostic identifier (ddd.domainServices.complexLogic)
remain valid.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
* **rules:** NoStatementsLogicRule now reports match expressions in
domain model methods as violations. Consumer projects that used match
to side-step the previous rule (which only knew about if and switch)
will start failing — move the conditional dispatch into an Action or
Domain Service. Concurrently, the rule now skips Closure and
ArrowFunction subtrees, so codebases that relied on closure-bodies
being scanned as part of the model will see fewer errors; this is a
loosening, intended to align with Laravel's idiomatic accessor pattern.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
* **rules:** ParentChildTransactionRule now requires Eloquent
relationships to declare a typed return (BelongsTo / MorphTo) to be
recognised as parent relations. Consumer projects that still use the
pre-7.x Laravel idiom of untyped relationship methods (public function
user() { return $this->belongsTo(User::class); }) will no longer be
flagged for direct saves on those classes — adding the : BelongsTo
return type restores detection. Concurrently, classes that inherit a
typed parent relationship from an ancestor are now correctly flagged;
codebases relying on the previous narrow detection may see new errors.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
* **rules:** ModelValidationRule no longer accepts Enigma\ValidatorTrait
or any trait whose short name happens to be Validatable. Consumer projects
must install opscale-co/validations and use Opscale\Validations\Validatable
on each Eloquent model under \Models (or on a shared abstract ancestor).
The error message text also changed; baselines that match by message
substring will need to be regenerated, but baselines keyed on the
diagnostic identifier (ddd.aggregates.modelValidation) remain valid.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>

### Features

* **rules:** allow Eloquent typing and Log facade in CommunicationLayerRule ([461a56a](https://github.com/opscale-co/strict-rules/commit/461a56a33b3bd91f462fe2676a30844defdb29f7))
* **rules:** allow Eloquent typing and Log facade in OrchestrationLayerRule ([ea92eed](https://github.com/opscale-co/strict-rules/commit/ea92eed3043117c3dd8f69b620ad782d9abe0b9a))
* **rules:** allow Illuminate\Support\Carbon for date typing in RepresentationLayerRule ([6fe75a2](https://github.com/opscale-co/strict-rules/commit/6fe75a26143de4d0c489447a40dcc9efd575ea96))
* **rules:** allow MCP, Nova, Inertia, Sanctum and Log in InteractionLayerRule ([80d2822](https://github.com/opscale-co/strict-rules/commit/80d282276b772ec8f0c0935a757aa13f4c1ce6db))
* **rules:** allow Support helpers and Log facade in TransformationLayerRule ([ec6b058](https://github.com/opscale-co/strict-rules/commit/ec6b0589cc1cf62f450392f79a1df44206e69a18))
* **rules:** broaden EloquentRestrictionRule scope and exempt \Services\* ([7cb154d](https://github.com/opscale-co/strict-rules/commit/7cb154df236decd064ca829cae009bc4b60211db))
* **rules:** detect match expressions and skip closures in NoStatementsLogicRule ([9ee2399](https://github.com/opscale-co/strict-rules/commit/9ee23998628ea5b63c63243b4c0ddec2a40a75da))
* **rules:** enforce Validatable trait from opscale-co/validations in ModelValidationRule ([72a9020](https://github.com/opscale-co/strict-rules/commit/72a90200d71d9d1526182a3da5f7c086d1927ff9))
* **rules:** exempt wrapping throws and walk multi-class files in NoDummyCatchesRule ([3d27be5](https://github.com/opscale-co/strict-rules/commit/3d27be55f10f7642f4a72ea99f2759a8cc08ceb7))
* **rules:** expand allowed instantiations and walk multi-class files in DisallowInstantiationRule ([8710890](https://github.com/opscale-co/strict-rules/commit/8710890a035a11fe112099de77bc433e9572326f))
* **rules:** measure class lines (not file lines) and walk multi-class files in MaxLinesRule ([a2588ff](https://github.com/opscale-co/strict-rules/commit/a2588ff17146b08e6dc46f0187db980fb423685c))
* **rules:** remove EnforceLogicHandlingRule (consolidation) ([8bd1b4f](https://github.com/opscale-co/strict-rules/commit/8bd1b4f1c5d4ea227d33c56281f8a5e2fda6d5b8))
* **rules:** replace import-counting with operation-based detection in ComplexLogicRule ([22c7e66](https://github.com/opscale-co/strict-rules/commit/22c7e669223d060c784872ca80673be78cf9c7d0))
* **rules:** rewrite EntityCountRule with Collector + per-subdomain aggregation ([d37c917](https://github.com/opscale-co/strict-rules/commit/d37c91774d3ad87ad08e16b34b2ff0116f69fc42))
* **rules:** skip magic methods and walk multi-class files in ConditionalOverrideRule ([7a59519](https://github.com/opscale-co/strict-rules/commit/7a59519634e5d4fbb4ee343dc1a9485a2ca7ee1c))
* **rules:** tighten NoAccesorMutatorRule and walk multi-class files ([e13c0cd](https://github.com/opscale-co/strict-rules/commit/e13c0cd5ded55edd3c63978e7490d463f0f2f431))
* **rules:** tighten ParentChildTransactionRule to return-type-only with inheritance walk ([26ecc97](https://github.com/opscale-co/strict-rules/commit/26ecc9765cb1c1b13501aa32e777bdfe880d59d2))
* **rules:** walk all TraitUse stmts, walk inheritance, detect disablement in EnforceUlidsRule ([9ab2940](https://github.com/opscale-co/strict-rules/commit/9ab2940ffac5b564b5b8294dcf00cf5010e940ad))
* **rules:** walk every class in BaseNamespaceRule and report at the class line ([6e40af6](https://github.com/opscale-co/strict-rules/commit/6e40af6d57013f2151c132bd0aa387f35bc0e49e))
* **rules:** walk inheritance + multi-class files in EnforceCastRule ([80d3b76](https://github.com/opscale-co/strict-rules/commit/80d3b76875044b9e5638abff8affe4edb3b7c837))
* **rules:** walk multi-class files and detect transitive interfaces in EnforceImplementationRule ([b3f7802](https://github.com/opscale-co/strict-rules/commit/b3f7802c6629b18d9431423ec8644e13d2a2b03a))
* **rules:** walk multi-class files in HelpersRestrictionRule ([50a84f0](https://github.com/opscale-co/strict-rules/commit/50a84f0a23657df7ce250a5668dc50a5b8bc4927))
* **rules:** walk multi-class files in ParentCallRule ([5f4b656](https://github.com/opscale-co/strict-rules/commit/5f4b656fa5cbf56037c4804913eded8d8f305b95))


Generating notes for version 1.2.0

## [1.1.3](https://github.com/opscale-co/strict-rules/compare/v1.1.2...v1.1.3) (2025-09-19)


Generating notes for version 1.1.3

## [1.1.2](https://github.com/opscale-co/strict-rules/compare/v1.1.1...v1.1.2) (2025-08-26)


Generating notes for version 1.1.2

## [1.1.1](https://github.com/opscale-co/strict-rules/compare/v1.1.0...v1.1.1) (2025-06-25)


Generating notes for version 1.1.1

## [1.1.0](https://github.com/opscale-co/strict-rules/compare/v1.0.0...v1.1.0) (2025-06-25)

### Features

* **lot of changes:** lot of changes ([2748298](https://github.com/opscale-co/strict-rules/commit/2748298201c92700e60f9b67923f2657e304e114))


Generating notes for version 1.1.0

## 1.0.0 (2025-06-18)

### Features

* First release with working pipeline ([23d9da3](https://github.com/opscale-co/strict-rules/commit/23d9da344bc71f876f17fc3c24a90ccb8cfbe3f4))
* **project:** fix node typing management ([14851cc](https://github.com/opscale-co/strict-rules/commit/14851cc9d1d7477b7c25e58c7c36d7e36cff74cf))

### Bug Fixes

* Fixed secrets reference ([1a5b8e8](https://github.com/opscale-co/strict-rules/commit/1a5b8e891a4d2e3de26d7bfcd1430b79d3264045))
* Minor fixes ([2d14d4d](https://github.com/opscale-co/strict-rules/commit/2d14d4df3ec5da5624bf5e434229cce96c4e0d54))
* Solved composer.json bad formatting ([8ec3bcd](https://github.com/opscale-co/strict-rules/commit/8ec3bcd1e6e5ebbaff931725bc748dfddf6086ae))
* Update workflow ([4e8ecae](https://github.com/opscale-co/strict-rules/commit/4e8ecae57bd3e86a5d35d4c0f7b53d99fa4335f3))
* Update workflow ([96f7b8e](https://github.com/opscale-co/strict-rules/commit/96f7b8e42d60b0800be50a62b5ea978bcd231011))
* Updated envs ([955eb1a](https://github.com/opscale-co/strict-rules/commit/955eb1adc300b92953539014cc8d5954e98855a5))
* Updated workflow ([5524041](https://github.com/opscale-co/strict-rules/commit/552404145108a48cc69ef615780180e87902a5cd))
* Updated workflow ([bc15b84](https://github.com/opscale-co/strict-rules/commit/bc15b8473fd7ae164e8a7926f5e8007b50804509))
* Updated workflow ([b82c0dc](https://github.com/opscale-co/strict-rules/commit/b82c0dcdd03d7628423c3ff1f3566bf24c6688b2))


Generating notes for version 1.0.0

# Changelog

## v1.0.0 - 2025-06-10

First working version, it has rules for CLEAN, DDD, SOLID and code smells.
