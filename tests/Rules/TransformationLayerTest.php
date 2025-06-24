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
    public function rule(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Services/ExternalAPIService.php',
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
            ],
        ]);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new TransformationLayerRule($reflectionProvider);
    }
}
