<?php

namespace Opscale\Tests\Rules;

use Opscale\Rules\DDD\Entities\EnforceUlidsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(EnforceUlidsRule::class)]
class EnforceUlidsTest extends RuleTestCase
{
    #[Test]
    public function rule(): void
    {
        $this->analyse([__DIR__ . '/../fixtures/Models/User.php'], [
            [
                'Model class "Opscale\Models\User" must use the "HasUlids" trait ' .
                'to ensure consistent ID handling with ULIDs.',
                11,
            ],
        ]);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new EnforceUlidsRule($reflectionProvider);
    }
}
