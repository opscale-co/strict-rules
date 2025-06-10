<?php

namespace Opscale\Tests\Rules;

use PHPStan\Rules\Rule;
use Opscale\Rules\SOLID\OCP\ConditionalOverrideRule;
use PHPStan\Testing\RuleTestCase;

class ConditionalOverrideTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $broker = $this->createReflectionProvider();
        return new ConditionalOverrideRule($broker);
    }

    public function testRule(): void
    {
        $this->analyse([
                __DIR__ . '/../app/Models/Product.php'
            ], [
                [
                    'Method "Opscale\Models\Product::isInStock()" must be final unless annotated with #[\Override] or @overridable. ' .
                    'Public and protected methods should be explicitly marked as final to follow the Open/Closed Principle.',
                    17,
                ],
         ]);
    }
}