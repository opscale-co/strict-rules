<?php

namespace Opscale\Tests\Rules;

use PHPStan\Rules\Rule;
use Opscale\Rules\CLEAN\Transformation\TransformationLayerRule;
use PHPStan\Testing\RuleTestCase;

class TransformationLayerTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $broker = $this->createReflectionProvider();
        return new TransformationLayerRule($broker);
    }

    public function testRule(): void
    {
        $this->analyse([
                __DIR__ . '/../app/Services/ExternalAPIService.php'
            ], [
                [
                    'Clean Architecture violation: Class "Opscale\Services\ExternalAPIService" from layer 3 cannot depend on "Illuminate\Support\Facades\Response". ' .
                    'This class is not allowed in this layer, it does not comply with the layer purpose.',
                    8,
                ],
                [
                    'Clean Architecture violation: Class "Opscale\Services\ExternalAPIService" from layer 3 cannot depend on "Opscale\Jobs\CleanOldProducts" from layer 4. ' .
                    'Layers can only use equal or lower layers and communicate via events upwards.',
                    9,
                ]
         ]);
    }
}