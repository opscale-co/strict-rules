## Support us

Support Opscale

At Opscale, we’re passionate about contributing to the open-source community by providing solutions that help businesses scale efficiently. If you’ve found our tools helpful, here are a few ways you can show your support:

⭐ **Star this repository** to help others discover our work and be part of our growing community. Every star makes a difference!

💬 **Share your experience** by leaving a review on [Trustpilot](https://www.trustpilot.com/review/opscale.co) or sharing your thoughts on social media. Your feedback helps us improve and grow!

📧 **Send us feedback** on what we can improve at [feedback@opscale.co](mailto:feedback@opscale.co). We value your input to make our tools even better for everyone.

🙏 **Get involved** by actively contributing to our open-source repositories. Your participation benefits the entire community and helps push the boundaries of what’s possible.

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

You can install the package in to a Laravel project via composer:

```bash

composer require opscale-co/strict-rules --dev

```

Next up, you must create a `phpstan.neon` file in the root of your project with this content:

```

includes:
    - vendor/nunomaduro/larastan/extension.neon
    - vendor/opscale-co/strict-rules/rules.clean.neon
    - vendor/opscale-co/strict-rules/rules.ddd.neon
    - vendor/opscale-co/strict-rules/rules.smells.neon
    - vendor/opscale-co/strict-rules/rules.solid.neon
parameters:
    level: 8
    phpVersion: 80200
    paths:
        - src

```

You are free to use only a subset of rules commenting the not needed guidelines.

## Usage

You can run the command vendor/bin/phpstan analyze to execute the rules.

## Testing

``` bash

npm run test

```

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