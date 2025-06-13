<?php

namespace Opscale\Tests\Rules;

use Opscale\Rules\Smells\EnforceLogicHandlingRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(EnforceLogicHandlingRule::class)]
class EnforceLogicHandlingTest extends RuleTestCase
{
    #[Test]
    public function rule(): void
    {
        $this->analyse([
            __DIR__ . '/../app/Jobs/CleanOldProducts.php',
        ],
            [
                [
                    '"Opscale\Jobs\CleanOldProducts" class contains try-catch block, exception handling is only allowed in logic. ' .
                    'Consider managing exceptions in Services and manage expected values anywhere else.',
                    25,
                ],
            ]);
    }

    protected function getRule(): Rule
    {
        $broker = $this->createReflectionProvider();

        return new EnforceLogicHandlingRule($broker);
    }
}
