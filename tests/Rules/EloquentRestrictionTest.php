<?php

namespace Opscale\Tests\Rules;

use PHPStan\Rules\Rule;
use Opscale\Rules\DDD\Repositories\EloquentRestrictionRule;
use PHPStan\Testing\RuleTestCase;

class EloquentRestrictionTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $broker = $this->createReflectionProvider();
        return new EloquentRestrictionRule($broker);
    }

    public function testRule(): void
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
}