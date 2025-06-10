<?php

namespace Opscale\Tests\Rules;

use PHPStan\Rules\Rule;
use Opscale\Rules\Smells\NoDummyCatchesRule;
use PHPStan\Testing\RuleTestCase;

class NoDummyCatchesTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $broker = $this->createReflectionProvider();
        return new NoDummyCatchesRule($broker);
    }

    public function testRule(): void
    {
        $this->analyse([
                __DIR__ . '/../app/Jobs/CleanOldProducts.php',
            ], 
            [
                [
                    'Empty catch block for exception type(s) "Exception". ' .
                    'Either handle the exception properly or remove the try-catch block.',
                    25,
                ]
            ]);
    }
}