<?php

namespace Opscale\Tests\Rules;

use Opscale\Rules\DDD\Aggregates\ModelValidationRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(ModelValidationRule::class)]
class ModelValidationTest extends RuleTestCase
{
    #[Test]
    public function detects_model_without_validate_method(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Models/Product.php',
        ],
            [
                [
                    'Model class "Opscale\Models\Product" must implement a "validate(string $key): array"',
                    10,
                ],
            ]);
    }

    #[Test]
    public function allows_model_with_validate_method(): void
    {
        $this->analyse([__DIR__ . '/../fixtures/Models/ValidatedModel.php'], []);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new ModelValidationRule($reflectionProvider);
    }
}
