## Support us

Support Opscale

At Opscale, we're passionate about contributing to the open-source community by providing solutions that help businesses scale efficiently. If you've found our tools helpful, here are a few ways you can show your support:

⭐ **Star this repository** to help others discover our work and be part of our growing community. Every star makes a difference!

💬 **Share your experience** by leaving a review on [Trustpilot](https://www.trustpilot.com/review/opscale.co) or sharing your thoughts on social media. Your feedback helps us improve and grow!

📧 **Send us feedback** on what we can improve at [feedback@opscale.co](mailto:feedback@opscale.co). We value your input to make our tools even better for everyone.

🙏 **Get involved** by actively contributing to our open-source repositories. Your participation benefits the entire community and helps push the boundaries of what's possible.

💼 **Hire us** if you need custom dashboards, admin panels, internal tools or MVPs tailored to your business. With our expertise, we can help you systematize operations or enhance your existing product. Contact us at hire@opscale.co to discuss your project needs.

Thanks for helping Opscale continue to scale! 🚀

## Description

Enforce software architecture guidelines for your Laravel projects with opinionated, battle-tested rules that promote maintainable, scalable code.

### Why Use Architectural Guidelines?

Modern software projects face increasing complexity as they scale. Without clear architectural boundaries, codebases become tangled, difficult to test, and expensive to maintain. Our approach focuses on **preventing architectural debt** before it accumulates, ensuring your Laravel applications remain clean and extensible as they grow. Learn more about our architectural philosophy in [software-architecture.md](src/Rules/1-software-architecture.md).

### How We Design Software Components

We follow a **business-centric design methodology** that starts with understanding the domain before writing code. Our systematic approach guides you through identifying business units, mapping information flows, modeling data architecture, and defining business rules that create value. This methodology ensures your software components genuinely reflect how the business operates, making them both maintainable and AI-friendly. Discover our complete design process in [design-methodology.md](src/Rules/2-design-methodology.md).

### What Guidelines We Cover

Through **real-world examples and data stories**, we implement three proven architectural approaches that work together to create robust Laravel applications. Each guideline is illustrated with practical scenarios that demonstrate both common problems and their solutions. See concrete implementations in [data-story.md](src/Rules/3-data-story.md).

### Supported Guidelines

| Guideline | Purpose | Key Concepts | Documentation |
|-----------|---------|--------------|---------------|
| **[DDD](src/Rules/DDD/assumptions.md)** | Domain modeling with Laravel pragmatism | Aggregates, Entities, Value Objects, Repositories, Domain Services | [DDD Assumptions](src/Rules/DDD/assumptions.md) |
| **[Clean Architecture](src/Rules/CLEAN/assumptions.md)** | Layered separation of concerns | Representation, Communication, Transformation, Orchestration, Interaction | [Clean Assumptions](src/Rules/CLEAN/assumptions.md) |
| **[SOLID](src/Rules/SOLID/assumptions.md)** | Code smell prevention through proven principles | SRP, OCP, LSP, ISP, DIP with practical Laravel application | [SOLID Assumptions](src/Rules/SOLID/assumptions.md) |

## Installation

[![Latest Version on Packagist](https://img.shields.io/packagist/v/opscale-co/strict-rules.svg?style=flat-square)](https://packagist.org/packages/opscale-co/strict-rules)

You can install the package into a Laravel project via composer:

```bash
composer require opscale-co/strict-rules --dev
```

Next up, create a `phpstan.neon` file in the root of your project:

```neon
includes:
    - vendor/larastan/larastan/extension.neon
    - vendor/opscale-co/strict-rules/rules.clean.neon
    - vendor/opscale-co/strict-rules/rules.ddd.neon
    - vendor/opscale-co/strict-rules/rules.smells.neon
    - vendor/opscale-co/strict-rules/rules.solid.neon

parameters:
    level: 8
    phpVersion: 80200
    paths:
        - src
        - app
```

You are free to use only a subset of rules — comment out the includes you don't need.

## Usage

```bash
vendor/bin/phpstan analyze --memory-limit=512M
```

The `--memory-limit=512M` flag is recommended: with all four rule sets active, the default 128MB limit can be insufficient on medium-sized projects.

### Package-agnostic namespace detection

The CLEAN-layer, DDD and Smells rules detect their target classes by **namespace segments**, not by a hard-coded `App\` prefix. The detection regex (`^(\w+)(\\\w+)*(\\Models\\)`, `^(\w+)(\\\w+)*(\\Services\\)`, etc.) accepts any first-segment package root:

- `App\Models\Order` → layer 1 (Representation) ✓
- `Opscale\Models\Order` → layer 1 ✓
- `Vendor\Package\Models\Order` → layer 1 ✓
- `Acme\Modules\Loans\Models\Order` → layer 1 ✓

This means the rules work for plain Laravel apps (`App\`), distributed packages (`opscale-co/strict-rules` itself uses `Opscale\`), and modular monoliths with custom namespace roots.

### Rule catalogue

Every rule preserves a stable PHPStan diagnostic identifier — useful when configuring `ignoreErrors` blocks or PHPStan baselines that should survive future versions.

#### DDD rules — `rules.ddd.neon`

| Rule | Identifier | Enforces |
|------|------------|----------|
| `ModelValidationRule` | `ddd.aggregates.modelValidation` | Eloquent models in `\Models\*` use `Opscale\Validations\Validatable` (directly or via inheritance). |
| `ParentChildTransactionRule` | `ddd.aggregates.parentChildTransaction` | Repositories / Services do not call `save()` on a parameter whose type (or any ancestor) declares a `BelongsTo` / `MorphTo` return method. |
| `NoStatementsLogicRule` | `ddd.domain.noStatementsLogic` | No `if` / `switch` / `match` / `for` / `foreach` / `while` / `do-while` in Eloquent model methods (closures and arrow functions are skipped). |
| `ComplexLogicRule` | `ddd.domainServices.complexLogic` | Classes outside `\Services\*` do not operate on more than 2 distinct Eloquent models via `StaticCall`, `new`, or `->save()` on a typed parameter. |
| `EnforceUlidsRule` | `ddd.entities.enforceUlids` | Eloquent models use `HasUlids` (directly or via any ancestor) and do not neutralise it via `$incrementing = true` or `$keyType = 'int'`. |
| `EloquentRestrictionRule` | `ddd.repositories.eloquentRestriction` | Eloquent CRUD calls (queries, retrieval, aggregates, persistence) only happen inside `\Models\Repositories\*` or `\Services\*`. Relationships, model state and serialization helpers are not in scope. |
| `BaseNamespaceRule` | `ddd.subdomains.baseNamespace` | Eloquent models live directly under a `\Models` namespace segment — no subfolders. |
| `EntityCountRule` | `ddd.subdomains.entityCount` | Each `\Models\*` subdomain has at most 25 concrete Eloquent entities (configurable). Implemented as `Collector` + `Rule<CollectedDataNode>`. |
| `EnforceCastRule` | `ddd.valueObjects.enforceCast` | Concrete classes under `\Models\ValueObjects\*` implement `Illuminate\Contracts\Database\Eloquent\CastsAttributes` (directly or via inheritance). |
| `NoAccesorMutatorRule` | `ddd.valueObjects.noAccesorMutator` | Eloquent models do not declare custom `set<Name>Attribute` / `get<Name>Attribute` methods or Laravel 9+ `Attribute` accessor methods. |

#### CLEAN rules — `rules.clean.neon`

Layer-allowed-imports rules. Each fires on classes in its own layer and verifies that imports come only from allowed sources (lower layers, framework allow-list, external allow-list, named facades).

| Rule | Identifier | Layer (number) |
|------|------------|----------------|
| `RepresentationLayerRule` | `clean.layer1.importNotAllowed` | 1 — `\Models\*` |
| `CommunicationLayerRule` | `clean.layer2.importNotAllowed` | 2 — `\Observers\*` |
| `TransformationLayerRule` | `clean.layer3.importNotAllowed` | 3 — `\Services\*`, `\Exceptions\*`, `\Contracts\*` |
| `OrchestrationLayerRule` | `clean.layer4.importNotAllowed` | 4 — `\Jobs\*`, `\Notifications\*` |
| `InteractionLayerRule` | `clean.layer5.importNotAllowed` | 5 — `\Console\*`, `\Http\*`, `\Nova\*`, `\Policies\*` |

Each layer's specific allow lists (framework, facades, externals) are documented in `src/Rules/CLEAN/<Layer>/documentation.md`. Notable defaults:

- **Interaction**: external imports include `Mcp\`, `PhpMcp\`, `Laravel\Nova\`, `Laravel\Sanctum\`, `Inertia\`. `Log` facade allowed.
- **Transformation**: framework imports include the specific Support helpers `Illuminate\Support\Arr`, `Illuminate\Support\Collection`, `Illuminate\Support\Number`, `Illuminate\Support\Str`. `Log` facade allowed.
- **Orchestration**: framework imports include `Illuminate\Database\Eloquent\` for Model type hints. `Log` facade allowed.
- **Communication**: framework imports include `Illuminate\Database\Eloquent\` for Model type hints. `Log` facade allowed.
- **Representation**: framework imports include `Illuminate\Support\Carbon` (Laravel's date wrapper). `Log` is intentionally NOT allowed — Models stay declarative.

#### SOLID rules — `rules.solid.neon`

| Rule | Identifier | Enforces |
|------|------------|----------|
| `MaxLinesRule` | `solid.srp.maxLines` | Each `Class_` / `Trait_` / `Enum_` body is under 500 lines (configurable). Measured per classlike, not per file. |
| `ConditionalOverrideRule` | `solid.ocp.conditionalOverride` | Public/protected methods are `final`, `abstract`, or annotated with `#[\Override]`. Magic methods (`__`-prefixed) are skipped. |
| `ParentCallRule` | `solid.lsp.parentCall` | An instance method that overrides a concrete parent method calls `parent::` somewhere in its body. |
| `EnforceImplementationRule` | `solid.isp.enforceImplementation` | Methods implementing an interface (directly or via parent class) are not stubs (empty body / single throw / single default-return). |
| `DisallowInstantiationRule` | `solid.dip.disallowInstantiation` | No direct `new ClassName()` outside constructors, except for: PHP built-ins; named Laravel/Carbon classes; subclasses of `Eloquent\Model`, `Mailable`, `Notification`, `JsonResource`; the suffix list (`*DTO`, `*Data`, `*Event`, ...); `self`/`parent`/`static`. |

#### Smells rules — `rules.smells.neon`

| Rule | Identifier | Enforces |
|------|------------|----------|
| `NoDummyCatchesRule` | `smells.noDummyCatches` | `catch` blocks are not empty, single-`return`, or single bare-throw. `throw new SomeClass(..., 0, $e)` (wrapping) is allowed. |
| `HelpersRestrictionRule` | `smells.helpersRestriction.helper` | No usage of Laravel global helpers (`auth()`, `cache()`, `config()`, ...) — use facades or DI. |

### Configuring exemptions

Most rules accept constructor arguments via NEON to soften them where appropriate:

```neon
services:
    -
        class: Opscale\Rules\DDD\Subdomains\EntityCountRule
        arguments:
            maxClasses: 50          # default 25
        tags:
            - phpstan.rules.rule

    -
        class: Opscale\Rules\SOLID\SRP\MaxLinesRule
        arguments:
            maxLines: 250            # default 500
        tags:
            - phpstan.rules.rule

    -
        class: Opscale\Rules\SOLID\DIP\DisallowInstantiationRule
        arguments:
            additionalAllowedClasses:
                - 'My\Project\AllowedFactory'
        tags:
            - phpstan.rules.rule
```

Override the entries in your project's `phpstan.neon` only if the bundled defaults need adjustment.

### Multi-class files

All rules walk every classlike (`Class_`, `Trait_`, `Enum_`) declared in the file. Multi-class files are unusual but legal in PHP and are no longer a blind spot.

If your project has fixtures (or production code) with multiple classes per file, ensure your composer autoloader is aware of them via `autoload.classmap` — PSR-4 alone cannot find them.

## Testing

```bash
npm test
```

The package's own tests run Pest 3 on top of PHPStan's `RuleTestCase`. The `npm test` script wraps Pest with `php -d memory_limit=512M` so the strict-rules suite analysing itself does not hit OOM.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/opscale-co/.github/blob/main/CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email development@opscale.co instead of using the issue tracker.

## Credits

- [Opscale](https://github.com/opscale-co)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
