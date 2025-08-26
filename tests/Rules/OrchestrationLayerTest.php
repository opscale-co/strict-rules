<?php

namespace Opscale\Tests\Rules;

use Opscale\Rules\CLEAN\Orchestration\OrchestrationLayerRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(OrchestrationLayerRule::class)]
class OrchestrationLayerTest extends RuleTestCase
{
    #[Test]
    public function detects_layer_violations(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Jobs/CleanOldProducts.php',
        ], [
            [
                'Clean Architecture violation: Class "Opscale\Jobs\CleanOldProducts" from layer 4 cannot depend on "Opscale\Http\Controllers\ProductsController" from layer 5. ' .
                'Layers can only use equal or lower layers and communicate via events upwards.',
                10,
            ],
            [
                'Clean Architecture violation: Class "Opscale\Jobs\CleanOldProducts" from layer 4 cannot depend on "Illuminate\Support\Facades\Http". ' .
                'This import is not allowed in this layer according to facade, framework, project, or external import rules.',
                11,
            ],
        ]);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new OrchestrationLayerRule($reflectionProvider);
    }
}
