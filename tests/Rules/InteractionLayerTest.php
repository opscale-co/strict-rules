<?php

namespace Opscale\Tests\Rules;

use Opscale\Rules\CLEAN\Interaction\InteractionLayerRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(InteractionLayerRule::class)]
class InteractionLayerTest extends RuleTestCase
{
    #[Test]
    public function rule(): void
    {
        $this->analyse([
            __DIR__ . '/../app/Http/Controllers\ProductController.php',
        ], [
            [
                'Clean Architecture violation: Class "Opscale\Http\Controllers\ProductController" from layer 5 cannot depend on "Illuminate\Support\Facades\DB". ' .
                'This class is not allowed in this layer, it does not comply with the layer purpose.',
                7,
            ],
        ]);
    }

    protected function getRule(): Rule
    {
        $broker = $this->createReflectionProvider();

        return new InteractionLayerRule($broker);
    }
}
