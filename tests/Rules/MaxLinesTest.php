<?php

namespace Opscale\Tests\Rules;

use Opscale\Rules\SOLID\SRP\MaxLinesRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(MaxLinesRule::class)]
class MaxLinesTest extends RuleTestCase
{
    #[Test]
    public function rule(): void
    {
        $this->analyse([
            __DIR__ . '/../app/Models/User.php',
        ], [
            [
                'Class "Opscale\Models\User" has 57 lines, which exceeds the maximum allowed 50 lines. ' .
                'Consider breaking this class into smaller classes to follow the Single Responsibility Principle.',
                59,
            ],
        ]);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new MaxLinesRule($reflectionProvider, 50);
    }
}
