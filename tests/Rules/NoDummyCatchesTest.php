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
    public function detects_empty_catch_block(): void
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

    #[Test]
    public function allows_valid_exception_handling(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Jobs/ValidExceptionHandling.php',
        ], []);
    }

    #[Test]
    public function detects_multiple_dummy_catches(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Jobs/MultipleDummyCatches.php',
        ], [
            [
                'Empty catch block for exception type(s) "Exception". ' .
                'Either handle the exception properly or remove the try-catch block.',
                20,
            ],
            [
                'Empty catch block for exception type(s) "InvalidArgumentException". ' .
                'Either handle the exception properly or remove the try-catch block.',
                26,
            ],
            [
                'Empty catch block for exception type(s) "RuntimeException". ' .
                'Either handle the exception properly or remove the try-catch block.',
                28,
            ],
        ]);
    }

    #[Test]
    public function ignores_file_without_try_catch_blocks(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Models/ValidSmallUser.php',
        ], []);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new NoDummyCatchesRule($reflectionProvider);
    }
}
