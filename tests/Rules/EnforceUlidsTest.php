<?php

namespace Opscale\Tests\Rules;

use PHPStan\Rules\Rule;
use Opscale\Rules\DDD\Entities\EnforceUlidsRule;
use PHPStan\Testing\RuleTestCase;

class EnforceUlidsTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $broker = $this->createReflectionProvider();
        return new EnforceUlidsRule($broker);
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/../app/Models/User.php'], [
            [
                'Model class "Opscale\Models\User" must use the "HasUlids" trait ' .
                'to ensure consistent ID handling with ULIDs.',
                11,
            ],
        ]);
    }
}