<?php

namespace Opscale\Tests\Rules;

use PHPStan\Rules\Rule;
use Opscale\Rules\SOLID\DIP\DisallowInstantiationRule;
use PHPStan\Testing\RuleTestCase;

class DisallowInstantiationTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $broker = $this->createReflectionProvider();
        return new DisallowInstantiationRule($broker);
    }

    public function testRule(): void
    {
        $this->analyse([
                __DIR__ . '/../app/Services/ExternalAPIService.php'
            ], [
                [
                    'Class "Opscale\Services\ExternalAPIService" violates Dependency Inversion Principle ' .
                    'by directly instantiating "Opscale\Services\BatchingService" in method "canBatch()". ' .
                    'Consider injecting the dependency through constructor or method parameters.',
                    23,
                ]
         ]);
    }
}