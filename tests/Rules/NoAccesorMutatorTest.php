<?php

namespace Opscale\Tests\Rules;

use PHPStan\Rules\Rule;
use Opscale\Rules\DDD\ValueObjects\NoAccesorMutatorRule;
use PHPStan\Testing\RuleTestCase;

class NoAccesorMutatorTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $broker = $this->createReflectionProvider();
        return new NoAccesorMutatorRule($broker);
    }

    public function testRule(): void
    {
        $this->analyse([
                __DIR__ . '/../app/Models/Product.php',
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
}