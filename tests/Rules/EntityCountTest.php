<?php

namespace Opscale\Tests\Rules;

use Opscale\Rules\DDD\Subdomains\EntityCountRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(EntityCountRule::class)]
class EntityCountTest extends RuleTestCase
{
    #[Test]
    public function rule(): void
    {
        $this->analyse([
            __DIR__ . '/../app/Models/User.php',
            __DIR__ . '/../app/Models/Product.php',
        ], [
            [
                'Subdomain has 2 entities, which exceeds the maximum of 1 entities. ' .
                'Consider splitting this subdomain into smaller, more focused subdomains.',
                3,
            ],
        ]);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new EntityCountRule($reflectionProvider, 1);
    }
}
