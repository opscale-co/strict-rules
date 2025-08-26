<?php

namespace Opscale\Tests\Rules;

use Opscale\Rules\SOLID\DIP\DisallowInstantiationRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(DisallowInstantiationRule::class)]
class DisallowInstantiationTest extends RuleTestCase
{
    #[Test]
    public function detects_direct_instantiation(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Services/ExternalAPIService.php',
        ], [
            [
                'Class "Opscale\Services\ExternalAPIService" violates Dependency Inversion Principle ' .
                'by directly instantiating "Opscale\Services\BatchingService" in method "canBatch()". ' .
                'Consider injecting the dependency through constructor or method parameters.',
                24,
            ],
        ]);
    }

    #[Test]
    public function allows_proper_dependency_injection(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Services/ValidDependencyInjection.php',
        ], []);
    }

    #[Test]
    public function detects_multiple_instantiation_violations(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Services/MultipleViolations.php',
        ], [
            [
                'Class "Opscale\Services\MultipleViolations" violates Dependency Inversion Principle ' .
                'by directly instantiating "Opscale\Services\BatchingService" in method "processData()". ' .
                'Consider injecting the dependency through constructor or method parameters.',
                13,
            ],
            [
                'Class "Opscale\Services\MultipleViolations" violates Dependency Inversion Principle ' .
                'by directly instantiating "Opscale\Models\User" in method "processData()". ' .
                'Consider injecting the dependency through constructor or method parameters.',
                14,
            ],
            [
                'Class "Opscale\Services\MultipleViolations" violates Dependency Inversion Principle ' .
                'by directly instantiating "Opscale\Services\BatchingService" in method "anotherMethod()". ' .
                'Consider injecting the dependency through constructor or method parameters.',
                27,
            ],
            [
                'Class "Opscale\Services\MultipleViolations" violates Dependency Inversion Principle ' .
                'by directly instantiating "Opscale\Models\User" in method "createUserInstance()". ' .
                'Consider injecting the dependency through constructor or method parameters.',
                33,
            ],
        ]);
    }

    #[Test]
    public function ignores_files_without_instantiations(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Models/ValidSmallUser.php',
        ], []);
    }

    #[Test]
    public function allows_built_in_class_instantiations(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Jobs/ValidExceptionHandling.php',
        ], []);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new DisallowInstantiationRule($reflectionProvider);
    }
}
