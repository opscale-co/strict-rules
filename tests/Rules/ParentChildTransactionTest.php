<?php

namespace Opscale\Tests\Rules;

use PHPStan\Rules\Rule;
use Opscale\Rules\DDD\Aggregates\ParentChildTransactionRule;
use PHPStan\Testing\RuleTestCase;

class ParentChildTransactionTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $broker = $this->createReflectionProvider();
        return new ParentChildTransactionRule($broker);
    }

    public function testRule(): void
    {
        $this->analyse([
                __DIR__ . '/../app/Models/Repositories/ProductRepository.php',
            ], 
            [
                [
                    'Direct save() on model "Opscale\Models\Product" is not allowed. ' .
                    'Models with parent relationships (belongsTo) should only be saved through their parent aggregates.',
                    12,
                ]
            ]);
    }
}