<?php

namespace Opscale\Tests\Rules;

use Opscale\Rules\SOLID\OCP\ConditionalOverrideRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(ConditionalOverrideRule::class)]
class ConditionalOverrideTest extends RuleTestCase
{
    #[Test]
    public function rule(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Models/Product.php',
        ], [
            [
                'Method "' . \Opscale\Models\Product::class . '::isInStock()" must be final unless annotated with #[\Override] or @overridable. ' .
                'Public and protected methods should be explicitly marked as final to follow the Open/Closed Principle.',
                17,
            ],
        ]);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new ConditionalOverrideRule($reflectionProvider);
    }
}
