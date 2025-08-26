<?php

namespace Opscale\Tests\Rules;

use Opscale\Rules\CLEAN\Transformation\TransformationLayerRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(TransformationLayerRule::class)]
class TransformationLayerTest extends RuleTestCase
{
    #[Test]
    public function detects_layer_violations(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Services/ExternalAPIService.php',
        ], [
            [
                'Clean Architecture violation: Class "Opscale\Services\ExternalAPIService" from layer 3 cannot depend on "Illuminate\Support\Facades\Response". ' .
                'This import is not allowed in this layer according to facade, framework, project, or external import rules.',
                8,
            ],
            [
                'Clean Architecture violation: Class "Opscale\Services\ExternalAPIService" from layer 3 cannot depend on "Opscale\Jobs\CleanOldProducts" from layer 4. ' .
                'Layers can only use equal or lower layers and communicate via events upwards.',
                9,
            ],
        ]);
    }

    #[Test]
    public function allows_valid_imports(): void
    {
        // Response facade should now be allowed as framework import for Transformation layer
        $this->analyse([
            __DIR__ . '/../fixtures/Services/ValidService.php',
        ], []);
    }

    #[Test]
    public function allows_project_imports_from_lower_layers(): void
    {
        // Services (layer 3) should be able to use Models (layer 1)
        $this->analyse([
            __DIR__ . '/../fixtures/Services/ServiceUsingModels.php',
        ], []);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new TransformationLayerRule($reflectionProvider);
    }
}
