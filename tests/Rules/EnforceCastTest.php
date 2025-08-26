<?php

namespace Opscale\Tests\Rules;

use Opscale\Rules\DDD\ValueObjects\EnforceCastRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(EnforceCastRule::class)]
class EnforceCastTest extends RuleTestCase
{
    #[Test]
    public function detects_value_object_without_casts_attributes_interface(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Models/ValueObjects/Address.php',
        ],
            [
                [
                    'ValueObject class "Opscale\Models\ValueObjects\Address" must implement "Illuminate\Contracts\Database\Eloquent\CastsAttributes" interface. ' .
                    'This interface is required for classes that will be used as Value Objects.',
                    9,
                ],
            ]);
    }

    #[Test]
    public function allows_value_object_with_casts_attributes_interface(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Models/ValueObjects/ValidAddress.php',
        ], []);
    }

    #[Test]
    public function ignores_non_value_object_classes(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Models/DTOs/NonValueObject.php',
        ], []);
    }

    #[Test]
    public function detects_multiple_value_objects_in_different_files(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Models/ValueObjects/Address.php',
            __DIR__ . '/../fixtures/Models/ValueObjects/ValidAddress.php',
        ], [
            [
                'ValueObject class "Opscale\Models\ValueObjects\Address" must implement "Illuminate\Contracts\Database\Eloquent\CastsAttributes" interface. ' .
                'This interface is required for classes that will be used as Value Objects.',
                9,
            ],
        ]);
    }

    #[Test]
    public function ignores_classes_outside_value_objects_namespace(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Models/User.php',
            __DIR__ . '/../fixtures/Models/ValidSmallUser.php',
        ], []);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new EnforceCastRule($reflectionProvider);
    }
}
