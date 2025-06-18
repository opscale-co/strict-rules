<?php

namespace Opscale\Tests\Rules;

use Opscale\Rules\SOLID\DIP\DisallowInstantiationRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(DisallowInstantiationRule::class)]
class DisallowInstantiationTest extends RuleTestCase
{
    #[Test]
    public function rule(): void
    {
        $this->analyse([
            __DIR__ . '/../app/Services/ExternalAPIService.php',
        ], [
            [
                'Class "Opscale\Services\ExternalAPIService" violates Dependency Inversion Principle ' .
                'by directly instantiating "Opscale\Services\BatchingService" in method "canBatch()". ' .
                'Consider injecting the dependency through constructor or method parameters.',
                23,
            ],
        ]);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new DisallowInstantiationRule($reflectionProvider);
    }
}
