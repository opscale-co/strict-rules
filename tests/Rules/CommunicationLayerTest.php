<?php

namespace Opscale\Tests\Rules;

use PHPStan\Rules\Rule;
use Opscale\Rules\CLEAN\Communication\CommunicationLayerRule;
use PHPStan\Testing\RuleTestCase;

class CommunicationLayerTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $broker = $this->createReflectionProvider();
        return new CommunicationLayerRule($broker);
    }

    public function testRule(): void
    {
        $this->analyse([
                __DIR__ . '/../app/Observers/ProductObserver.php'
            ], [
                [
                    'Clean Architecture violation: Class "Opscale\Observers\ProductObserver" from layer 2 cannot depend on "Illuminate\Support\Facades\Response". ' .
                    'This class is not allowed in this layer, it does not comply with the layer purpose.',
                    6,
                ],
                [
                    'Clean Architecture violation: Class "Opscale\Observers\ProductObserver" from layer 2 cannot depend on "Opscale\Jobs\CleanOldProducts" from layer 4. ' .
                    'Layers can only use equal or lower layers and communicate via events upwards.',
                    7,
                ]
         ]);
    }
}