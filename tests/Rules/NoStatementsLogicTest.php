<?php

namespace Opscale\Tests\Rules;

use Opscale\Rules\DDD\Domain\NoStatementsLogicRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(NoStatementsLogicRule::class)]
class NoStatementsLogicTest extends RuleTestCase
{
    #[Test]
    public function rule(): void
    {
        $this->analyse([__DIR__ . '/../app/Models/User.php'], [
            [
                'Method "' . \Opscale\Models\User::class . '::getEmail" contains a "if" ' .
                'statement which is not allowed in domain model classes.',
                51,
            ],
        ]);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new NoStatementsLogicRule($reflectionProvider);
    }
}
