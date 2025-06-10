<?php

namespace Opscale\Tests\Rules;

use PHPStan\Rules\Rule;
use Opscale\Rules\DDD\Aggregates\ModelValidationRule;
use PHPStan\Testing\RuleTestCase;

class ModelValidationTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $broker = $this->createReflectionProvider();
        return new ModelValidationRule($broker);
    }

    public function testRule(): void
    {
        $this->analyse([
                __DIR__ . '/../app/Models/Product.php',
            ], 
            [
                [
                    'Model class "Opscale\Models\Product" must implement a "validate(string $key): array"',
                    10,
                ]
            ]);
    }
}