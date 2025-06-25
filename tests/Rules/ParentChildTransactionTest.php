<?php

namespace Opscale\Tests\Rules;

use Opscale\Rules\DDD\Aggregates\ParentChildTransactionRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(ParentChildTransactionRule::class)]
class ParentChildTransactionTest extends RuleTestCase
{
    #[Test]
    public function rule(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Models/Repositories/ProductRepository.php',
        ],
            [
                [
                    'Direct save() on model "Opscale\Models\Product" is not allowed. ' .
                    'Models with parent relationships (belongsTo) should only be saved through their parent aggregates.',
                    12,
                ],
            ]);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new ParentChildTransactionRule($reflectionProvider);
    }
}
