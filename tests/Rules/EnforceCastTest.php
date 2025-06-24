<?php

namespace Opscale\Tests\Rules;

use Opscale\Rules\DDD\ValueObjects\EnforceCastRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(EnforceCastRule::class)]
class EnforceCastTest extends RuleTestCase
{
    #[Test]
    public function rule(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Models/ValueObjects/Address.php',
        ],
            [
                [
                    'ValueObject class "Opscale\Models\ValueObjects\Address" must implement "Illuminate\Contracts\Database\Eloquent\CastsAttributes" interface. ' .
                    'This interface is required for classes that will be used as Value Objects.',
                    9,
                ],
            ]);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new EnforceCastRule($reflectionProvider);
    }
}
