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
    public function detects_eloquent_calls_outside_repositories(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Models/Repositories/UserRepository.php',
            __DIR__ . '/../fixtures/Models/Product.php',
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
                [
                    'Eloquent calls are only allowed within ' .
                    'Repositories: Found "belongsTo" call in "Opscale\Models\Product".',
                    41,
                ],
            ]);
    }

    #[Test]
    public function allows_eloquent_calls_within_repositories(): void
    {
        $this->analyse([__DIR__ . '/../fixtures/Models/Repositories/ProductRepository.php'], []);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new EloquentRestrictionRule($reflectionProvider);
    }
}
