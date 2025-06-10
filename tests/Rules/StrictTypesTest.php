<?php

namespace Opscale\Tests\Rules;

use PHPStan\Rules\Rule;
use Opscale\Rules\Smells\StrictTypesRule;
use PHPStan\Testing\RuleTestCase;

class StrictTypesTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $broker = $this->createReflectionProvider();
        return new StrictTypesRule($broker);
    }

    public function testRule(): void
    {
        $this->analyse([
                __DIR__ . '/../app/Jobs/CleanOldProducts.php',
            ], 
            [
                [
                    'Class "Opscale\Jobs\CleanOldProducts" must have declare(strict_types=1) at the beginning of the file.',
                    1,
                ]
            ]);
    }
}