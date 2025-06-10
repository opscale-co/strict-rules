<?php

namespace Opscale\Tests\Rules;

use PHPStan\Rules\Rule;
use Opscale\Rules\SOLID\SRP\MaxLinesRule;
use PHPStan\Testing\RuleTestCase;

class MaxLinesTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $broker = $this->createReflectionProvider();
        return new MaxLinesRule($broker, 50);
    }

    public function testRule(): void
    {
        $this->analyse([
                __DIR__ . '/../app/Models/User.php'
            ], [
                [
                    'Class "Opscale\Models\User" has 57 lines, which exceeds the maximum allowed 50 lines. ' .
                    'Consider breaking this class into smaller classes to follow the Single Responsibility Principle.',
                    3,
                ],
         ]);
    }
}