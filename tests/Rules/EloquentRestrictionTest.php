<?php

namespace Opscale\Tests\Rules;

use Opscale\Rules\DDD\Repositories\EloquentRestrictionRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(EloquentRestrictionRule::class)]
class EloquentRestrictionTest extends RuleTestCase
{
    #[Test]
    public function rule(): void
    {
        $this->analyse([
            __DIR__ . '/../app/Models/Repositories/UserRepository.php',
            __DIR__ . '/../app/Models/Product.php',
        ],
            [
                [
                    'Eloquent calls are only allowed within ' .
                    'Repositories: Found "where" call in "Opscale\Models\Product".',
                    14,
                ],
                [
                    'Eloquent calls are only allowed within ' .
                    'Repositories: Found "where" call in "Opscale\Models\Product".',
                    19,
                ],
            ]);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new EloquentRestrictionRule($reflectionProvider);
    }
}
