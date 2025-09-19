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
    public function detects_methods_without_final_or_override_annotation(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Models/Product.php',
        ], [
            [
                'Method "' . \Opscale\Models\Product::class . '::isInStock()" must be final unless annotated with #[\Override]. ' .
                'Public and protected methods should be explicitly marked as final to follow the Open/Closed Principle.',
                17,
            ],
        ]);
    }

    #[Test]
    public function allows_properly_marked_final_methods(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Models/SimpleModel.php',
        ], []);
    }

    #[Test]
    public function skips_abstract_methods(): void
    {
        $this->analyse([__DIR__ . '/../fixtures/Models/AbstractMethodsModel.php'], [
            // Only the concrete method without final or Override should trigger
            [
                'Method "' . \Opscale\Models\AbstractMethodsModel::class . '::getDescription()" must be final unless annotated with #[\Override]. ' .
                'Public and protected methods should be explicitly marked as final to follow the Open/Closed Principle.',
                16,
            ],
        ]);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new ConditionalOverrideRule($reflectionProvider);
    }
}
