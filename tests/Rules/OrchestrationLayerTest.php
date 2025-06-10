<?php

namespace Opscale\Tests\Rules;

use PHPStan\Rules\Rule;
use Opscale\Rules\CLEAN\Orchestration\OrchestrationLayerRule;
use PHPStan\Testing\RuleTestCase;

class OrchestrationLayerTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $broker = $this->createReflectionProvider();
        return new OrchestrationLayerRule($broker);
    }

    public function testRule(): void
    {
        $this->analyse([
                __DIR__ . '/../app/Jobs/CleanOldProducts.php'
            ], [
                [
                    'Clean Architecture violation: Class "Opscale\Jobs\CleanOldProducts" from layer 4 cannot depend on "Opscale\Http\Controllers\ProductsController" from layer 5. ' .
                    'Layers can only use equal or lower layers and communicate via events upwards.',
                    10,
                ],
                [
                    'Clean Architecture violation: Class "Opscale\Jobs\CleanOldProducts" from layer 4 cannot depend on "Illuminate\Support\Facades\Http". ' .
                    'This class is not allowed in this layer, it does not comply with the layer purpose.',
                    11,
                ],
         ]);
    }
}