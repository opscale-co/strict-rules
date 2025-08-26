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
    public function detects_layer_violations(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Http/Controllers\ProductController.php',
        ], [
            [
                'Clean Architecture violation: Class "Opscale\Http\Controllers\ProductController" from layer 5 cannot depend on "Illuminate\Support\Facades\DB". ' .
                'This import is not allowed in this layer according to facade, framework, project, or external import rules.',
                7,
            ],
        ]);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new InteractionLayerRule($reflectionProvider);
    }
}
