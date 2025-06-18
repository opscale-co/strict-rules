<?php

namespace Opscale\Tests\Rules;

use Opscale\Rules\CLEAN\Communication\CommunicationLayerRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(CommunicationLayerRule::class)]
class CommunicationLayerTest extends RuleTestCase
{
    #[Test]
    public function rule(): void
    {
        $this->analyse([
            __DIR__ . '/../app/Observers/ProductObserver.php',
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
            ],
        ]);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new CommunicationLayerRule($reflectionProvider);
    }
}
