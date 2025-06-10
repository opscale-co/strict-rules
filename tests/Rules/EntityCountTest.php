<?php

namespace Opscale\Tests\Rules;

use PHPStan\Rules\Rule;
use Opscale\Rules\DDD\Subdomains\EntityCountRule;
use PHPStan\Testing\RuleTestCase;

class EntityCountTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $broker = $this->createReflectionProvider();
        return new EntityCountRule($broker, 1);
    }

    public function testRule(): void
    {
        $this->analyse([
            __DIR__ . '/../app/Models/User.php',
            __DIR__ . '/../app/Models/Product.php'
            ], [
                [
                    'Subdomain has 2 entities, which exceeds the maximum of 1 entities. ' .
                    'Consider splitting this subdomain into smaller, more focused subdomains.',
                    3,
                ],
         ]);
    }
}