<?php

namespace Opscale\Tests\Rules;

use Opscale\Rules\Smells\HelpersRestrictionRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(HelpersRestrictionRule::class)]
class HelpersRestrictionTest extends RuleTestCase
{
    #[Test]
    public function detects_helper_function_usage(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Classes/ClassWithHelpers.php',
        ], [
            [
                'Helper function "auth()->user()" usage detected in "Opscale\Classes\ClassWithHelpers". ' .
                'Consider injecting the service directly instead of using helper functions.',
                9,
            ],
            [
                'Helper function "cache()->get()" usage detected in "Opscale\Classes\ClassWithHelpers". ' .
                'Consider injecting the service directly instead of using helper functions.',
                10,
            ],
            [
                'Helper function "config()" usage detected in "Opscale\Classes\ClassWithHelpers". ' .
                'Consider injecting the service directly instead of using helper functions.',
                11,
            ],
        ]);
    }

    #[Test]
    public function allows_classes_without_helper_usage(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Classes/ClassWithoutHelpers.php',
        ], []);
    }

    #[Test]
    public function detects_various_helper_functions(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Classes/ClassWithCustomHelpers.php',
        ], [
            [
                'Helper function "request()->all()" usage detected in "Opscale\Classes\ClassWithCustomHelpers". ' .
                'Consider injecting the service directly instead of using helper functions.',
                9,
            ],
            [
                'Helper function "session()->get()" usage detected in "Opscale\Classes\ClassWithCustomHelpers". ' .
                'Consider injecting the service directly instead of using helper functions.',
                10,
            ],
            [
                'Helper function "url()" usage detected in "Opscale\Classes\ClassWithCustomHelpers". ' .
                'Consider injecting the service directly instead of using helper functions.',
                11,
            ],
        ]);
    }

    #[Test]
    public function allows_static_method_calls_and_facades(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Classes/ClassWithStaticMethods.php',
        ], []);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new HelpersRestrictionRule($reflectionProvider);
    }
}
