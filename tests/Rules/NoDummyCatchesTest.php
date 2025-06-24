<?php

namespace Opscale\Tests\Rules;

use Opscale\Rules\Smells\NoDummyCatchesRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(NoDummyCatchesRule::class)]
class NoDummyCatchesTest extends RuleTestCase
{
    #[Test]
    public function rule(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Jobs/CleanOldProducts.php',
        ],
            [
                [
                    'Empty catch block for exception type(s) "Exception". ' .
                    'Either handle the exception properly or remove the try-catch block.',
                    25,
                ],
            ]);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new NoDummyCatchesRule($reflectionProvider);
    }
}
