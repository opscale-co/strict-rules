<?php

namespace Opscale\Tests\Rules;

use PHPStan\Rules\Rule;
use Opscale\Rules\DDD\ValueObjects\EnforceCastRule;
use PHPStan\Testing\RuleTestCase;

class EnforceCastTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $broker = $this->createReflectionProvider();
        return new EnforceCastRule($broker);
    }

    public function testRule(): void
    {
        $this->analyse([
                __DIR__ . '/../app/Models/ValueObjects/Address.php',
            ], 
            [
                [
                    'ValueObject class "Opscale\Models\ValueObjects\Address" must implement "Illuminate\Contracts\Database\Eloquent\CastsAttributes" interface. ' .
                    'This interface is required for classes that will be used as Value Objects.',
                    9,
                ],
            ]);
    }
}