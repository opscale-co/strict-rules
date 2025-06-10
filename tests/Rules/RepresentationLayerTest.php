<?php

namespace Opscale\Tests\Rules;

use PHPStan\Rules\Rule;
use Opscale\Rules\CLEAN\Representation\RepresentationLayerRule;
use PHPStan\Testing\RuleTestCase;

class RepresentationLayerTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $broker = $this->createReflectionProvider();
        return new RepresentationLayerRule($broker);
    }

    public function testRule(): void
    {
        $this->analyse([
                __DIR__ . '/../app/Models/Product.php'
            ], [
                [
                    'Clean Architecture violation: Class "Opscale\Models\Product" from layer 1 cannot depend on "Opscale\Jobs\CleanOldProducts" from layer 4. ' .
                    'Layers can only use equal or lower layers and communicate via events upwards.',
                    6,
                ],
                [
                    'Clean Architecture violation: Class "Opscale\Models\Product" from layer 1 cannot depend on "Illuminate\Support\Facades\Storage". ' .
                    'This class is not allowed in this layer, it does not comply with the layer purpose.',
                    7,
                ],
         ]);
    }
}