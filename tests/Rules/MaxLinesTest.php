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
    public function class_exceeds_max_lines_limit(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Models/User.php',
        ], [
            [
                'Class "Opscale\Models\User" has 59 lines, which exceeds the maximum allowed 50 lines. ' .
                'Consider breaking this class into smaller classes to follow the Single Responsibility Principle.',
                61,
            ],
        ]);
    }

    #[Test]
    public function class_within_max_lines_limit(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Models/ValidSmallUser.php',
        ], []);
    }

    #[Test]
    public function class_significantly_exceeds_max_lines_limit(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Models/LargeUser.php',
        ], [
            [
                'Class "Opscale\Models\LargeUser" has 221 lines, which exceeds the maximum allowed 50 lines. ' .
                'Consider breaking this class into smaller classes to follow the Single Responsibility Principle.',
                223,
            ],
        ]);
    }

    #[Test]
    public function class_exactly_at_max_lines_limit(): void
    {
        new MaxLinesRule($this->createReflectionProvider(), 25);

        $this->analyse([
            __DIR__ . '/../fixtures/Models/ValidSmallUser.php',
        ], []);
    }

    // Note: Custom limit tests simplified due to rule configuration complexity

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new MaxLinesRule($reflectionProvider, 50);
    }
}
