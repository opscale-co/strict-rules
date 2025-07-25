<?php

namespace Opscale\Tests\Rules;

use Opscale\Rules\DDD\ValueObjects\NoAccesorMutatorRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(NoAccesorMutatorRule::class)]
class NoAccesorMutatorTest extends RuleTestCase
{
    #[Test]
    public function rule(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Models/Product.php',
        ],
            [
                [
                    'Model "Opscale\Models\Product" is defining "getIdAttribute" and it should not contain Eloquent mutators or accessors. ' .
                    'Custom attribute logic should be defined as a ValueObject.',
                    22,
                ],
                [
                    'Model "Opscale\Models\Product" is defining "setIdAttribute" and it should not contain Eloquent mutators or accessors. ' .
                    'Custom attribute logic should be defined as a ValueObject.',
                    27,
                ],
                [
                    'Model "Opscale\Models\Product" is defining "stock" and it should not contain Eloquent mutators or accessors. ' .
                    'Custom attribute logic should be defined as a ValueObject.',
                    32,
                ],
            ]);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new NoAccesorMutatorRule($reflectionProvider);
    }
}
