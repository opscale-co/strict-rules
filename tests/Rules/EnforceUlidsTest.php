<?php

namespace Opscale\Tests\Rules;

use Opscale\Rules\DDD\Entities\EnforceUlidsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(EnforceUlidsRule::class)]
class EnforceUlidsTest extends RuleTestCase
{
    #[Test]
    public function detects_model_without_has_ulids_trait(): void
    {
        $this->analyse([__DIR__ . '/../fixtures/Models/User.php'], [
            [
                'Model class "Opscale\Models\User" must use the "HasUlids" trait ' .
                'to ensure consistent ID handling with ULIDs.',
                13,
            ],
        ]);
    }

    #[Test]
    public function allows_model_with_has_ulids_trait(): void
    {
        $this->analyse([__DIR__ . '/../fixtures/Models/ValidUlidUser.php'], []);
    }

    #[Test]
    public function ignores_non_model_classes(): void
    {
        $this->analyse([__DIR__ . '/../fixtures/Models/NonModelClass.php'], []);
    }

    #[Test]
    public function detects_multiple_models_in_same_file(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Models/User.php',
            __DIR__ . '/../fixtures/Models/ValidSmallUser.php',
        ], [
            [
                'Model class "Opscale\Models\User" must use the "HasUlids" trait ' .
                'to ensure consistent ID handling with ULIDs.',
                13,
            ],
            [
                'Model class "Opscale\Models\ValidSmallUser" must use the "HasUlids" trait ' .
                'to ensure consistent ID handling with ULIDs.',
                9,
            ],
        ]);
    }

    #[Test]
    public function allows_model_inherits_from_custom_base(): void
    {
        $this->analyse([__DIR__ . '/../fixtures/Models/LargeUser.php'], [
            [
                'Model class "Opscale\Models\LargeUser" must use the "HasUlids" trait ' .
                'to ensure consistent ID handling with ULIDs.',
                11,
            ],
        ]);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new EnforceUlidsRule($reflectionProvider);
    }
}
