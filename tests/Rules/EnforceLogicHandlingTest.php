<?php

namespace Opscale\Tests\Rules;

use Opscale\Rules\Smells\EnforceLogicHandlingRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(EnforceLogicHandlingRule::class)]
class EnforceLogicHandlingTest extends RuleTestCase
{
    #[Test]
    public function detects_exception_handling_in_jobs(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Jobs/CleanOldProducts.php',
        ],
            [
                [
                    '"Opscale\Jobs\CleanOldProducts" class contains try-catch block, exception handling is only allowed in logic. ' .
                    'Consider managing exceptions in Services, Models, or Observers and manage expected values anywhere else.',
                    25,
                ],
            ]);
    }

    #[Test]
    public function allows_exception_handling_in_services(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Services/LogicService.php',
        ], []);
    }

    #[Test]
    public function allows_controllers_without_exception_handling(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Http/Controllers/ValidController.php',
        ], []);
    }

    #[Test]
    public function allows_exception_handling_in_observers(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Observers/UserObserver.php',
        ], []);
    }

    #[Test]
    public function detects_exception_imports_in_jobs(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Jobs/JobWithExceptionImport.php',
        ], [
            [
                '"Opscale\Jobs\JobWithExceptionImport" class imports exception "Exception", exception imports are only allowed in logic layers. ' .
                'Consider managing exceptions in Services, Models, or Observers only.',
                5,
            ],
            [
                '"Opscale\Jobs\JobWithExceptionImport" class imports exception "InvalidArgumentException", exception imports are only allowed in logic layers. ' .
                'Consider managing exceptions in Services, Models, or Observers only.',
                6,
            ],
        ]);
    }

    #[Test]
    public function detects_exception_imports_in_controllers(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Http/Controllers/ControllerWithExceptionImport.php',
        ], [
            [
                '"Opscale\Http\Controllers\ControllerWithExceptionImport" class imports exception "RuntimeException", exception imports are only allowed in logic layers. ' .
                'Consider managing exceptions in Services, Models, or Observers only.',
                5,
            ],
        ]);
    }

    #[Test]
    public function detects_exception_handling_in_jobs_with_multiple_catches(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Jobs/ValidExceptionHandling.php',
        ], [
            [
                '"Opscale\Jobs\ValidExceptionHandling" class contains try-catch block, exception handling is only allowed in logic. ' .
                'Consider managing exceptions in Services, Models, or Observers and manage expected values anywhere else.',
                25,
            ],
            [
                '"Opscale\Jobs\ValidExceptionHandling" class contains try-catch block, exception handling is only allowed in logic. ' .
                'Consider managing exceptions in Services, Models, or Observers and manage expected values anywhere else.',
                36,
            ],
            [
                '"Opscale\Jobs\ValidExceptionHandling" class contains try-catch block, exception handling is only allowed in logic. ' .
                'Consider managing exceptions in Services, Models, or Observers and manage expected values anywhere else.',
                40,
            ],
        ]);
    }

    #[Test]
    public function ignores_classes_without_try_catch_blocks(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Models/ValidSmallUser.php',
            __DIR__ . '/../fixtures/Models/NonModelClass.php',
        ], []);
    }

    #[Test]
    public function detects_multiple_exception_handlers_in_same_file(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Jobs/MultipleDummyCatches.php',
        ], [
            [
                '"Opscale\Jobs\MultipleDummyCatches" class contains try-catch block, exception handling is only allowed in logic. ' .
                'Consider managing exceptions in Services, Models, or Observers and manage expected values anywhere else.',
                20,
            ],
            [
                '"Opscale\Jobs\MultipleDummyCatches" class contains try-catch block, exception handling is only allowed in logic. ' .
                'Consider managing exceptions in Services, Models, or Observers and manage expected values anywhere else.',
                26,
            ],
            [
                '"Opscale\Jobs\MultipleDummyCatches" class contains try-catch block, exception handling is only allowed in logic. ' .
                'Consider managing exceptions in Services, Models, or Observers and manage expected values anywhere else.',
                28,
            ],
        ]);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new EnforceLogicHandlingRule($reflectionProvider);
    }
}
