<?php

namespace Opscale\Tests\Rules;

use PHPStan\Rules\Rule;
use Opscale\Rules\CLEAN\Interaction\InteractionLayerRule;
use PHPStan\Testing\RuleTestCase;

class InteractionLayerTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $broker = $this->createReflectionProvider();
        return new InteractionLayerRule($broker);
    }

    public function testRule(): void
    {
        $this->analyse([
                __DIR__ . '/../app/Http/Controllers\ProductController.php'
            ], [
                [
                    'Clean Architecture violation: Class "Opscale\Http\Controllers\ProductController" from layer 5 cannot depend on "Illuminate\Support\Facades\DB". ' .
                    'This class is not allowed in this layer, it does not comply with the layer purpose.',
                    7,
                ]
         ]);
    }
}