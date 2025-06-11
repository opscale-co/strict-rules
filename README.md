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

Enforce software architecture guidelines for your Laravel packages.

Apply DDD, CLEAN and SOLID guidelines (opinionated) for enforcing rules for creating standardized and quality code across any Laravey project or package. You can also apply code smells and common performance checks.

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