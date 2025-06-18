<?php

namespace Opscale\Tests\Rules;

use Opscale\Rules\DDD\DomainServices\ComplexLogicRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(ComplexLogicRule::class)]
class ComplexLogicTest extends RuleTestCase
{
    #[Test]
    public function rule(): void
    {
        $this->analyse(
            [
                __DIR__ . '/../app/Models/Repositories/UserRepository.php',
                __DIR__ . '/../app/Services/BatchingService.php',
            ], [
                [
                    'Class "Opscale\Models\Repositories\UserRepository" is importing 2 Eloquent models, and it should not import more than 1. ' .
                    'Consider moving complex logic involving multiple models to a Service class in the Services namespace.',
                    8,
                ],
            ]);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new ComplexLogicRule($reflectionProvider);
    }
}
