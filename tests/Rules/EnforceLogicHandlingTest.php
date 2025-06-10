<?php

namespace Opscale\Tests\Rules;

use PHPStan\Rules\Rule;
use Opscale\Rules\Smells\EnforceLogicHandlingRule;
use PHPStan\Testing\RuleTestCase;

class EnforceLogicHandlingTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $broker = $this->createReflectionProvider();
        return new EnforceLogicHandlingRule($broker);
    }

    public function testRule(): void
    {
        $this->analyse([
                __DIR__ . '/../app/Jobs/CleanOldProducts.php',
            ], 
            [
                [
                    '"Opscale\Jobs\CleanOldProducts" class contains try-catch block, exception handling is only allowed in logic. ' .
                    'Consider managing exceptions in Services and manage expected values anywhere else.',
                    25,
                ]
            ]);
    }
}