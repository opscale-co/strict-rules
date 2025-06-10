<?php

namespace Opscale\Tests\Rules;

use PHPStan\Rules\Rule;
use Opscale\Rules\DDD\Subdomains\BaseNamespaceRule;
use PHPStan\Testing\RuleTestCase;

class BaseNamespaceTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $broker = $this->createReflectionProvider();
        return new BaseNamespaceRule($broker);
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/../app/Domain/User.php'], [
            [
                'Class "Opscale\Domain\User" extends Eloquent Model but ' .
                'is not in the "root\Models" namespace. ' . 
                'Eloquent models must be in the "root\Models" namespace.',
                3,
            ],
        ]);
    }
}