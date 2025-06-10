<?php

namespace Opscale\Tests\Rules;

use PHPStan\Rules\Rule;
use Opscale\Rules\SOLID\ISP\EnforceImplementationRule;
use PHPStan\Testing\RuleTestCase;

class EnforceImplementationTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $broker = $this->createReflectionProvider();
        return new EnforceImplementationRule($broker);
    }

    public function testRule(): void
    {
        $this->analyse([
                __DIR__ . '/../app/Services/BatchingService.php'
            ], [
                [
                    'Method "Opscale\Services\BatchingService::processBatch()" implements an interface but only returns a default value. ' .
                        'Provide a proper implementation instead.',
                    11,
                ],
                [
                    'Method "Opscale\Services\BatchingService::getBatchStatus()" implements an interface but only throws an exception. ' .
                    'Provide a proper implementation instead.',
                    16,
                ],
                [
                    'Method "Opscale\Services\BatchingService::completeBatch()" implements an interface but has an empty body. ' .
                    'Provide a proper implementation instead.',
                    21,
                ]
         ]);
    }
}