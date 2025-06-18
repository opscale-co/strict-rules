<?php

namespace Opscale\Tests\Rules;

use Opscale\Rules\SOLID\ISP\EnforceImplementationRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(EnforceImplementationRule::class)]
class EnforceImplementationTest extends RuleTestCase
{
    #[Test]
    public function rule(): void
    {
        $this->analyse([
            __DIR__ . '/../app/Services/BatchingService.php',
        ], [
            [
                'Method "' . \Opscale\Services\BatchingService::class . '::processBatch()" implements an interface but only returns a default value. ' .
                    'Provide a proper implementation instead.',
                11,
            ],
            [
                'Method "' . \Opscale\Services\BatchingService::class . '::getBatchStatus()" implements an interface but only throws an exception. ' .
                'Provide a proper implementation instead.',
                16,
            ],
            [
                'Method "' . \Opscale\Services\BatchingService::class . '::completeBatch()" implements an interface but has an empty body. ' .
                'Provide a proper implementation instead.',
                21,
            ],
        ]);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new EnforceImplementationRule($reflectionProvider);
    }
}
