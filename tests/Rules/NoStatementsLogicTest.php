<?php

namespace Opscale\Tests\Rules;

use PHPStan\Rules\Rule;
use Opscale\Rules\DDD\Domain\NoStatementsLogicRule;
use PHPStan\Testing\RuleTestCase;

class NoStatementsLogicTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $broker = $this->createReflectionProvider();
        return new NoStatementsLogicRule($broker);
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/../app/Models/User.php'], [
            [
                'Method "Opscale\Models\User::getEmail" contains a "if" ' . 
                'statement which is not allowed in domain model classes.',
                51,
            ],
        ]);
    }
}