<?php

namespace Opscale\Tests\Rules;

use Opscale\Rules\DDD\Subdomains\BaseNamespaceRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(BaseNamespaceRule::class)]
class BaseNamespaceTest extends RuleTestCase
{
    #[Test]
    public function rule(): void
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

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new BaseNamespaceRule($reflectionProvider);
    }
}
