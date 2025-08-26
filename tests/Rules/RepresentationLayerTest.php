<?php

namespace Opscale\Tests\Rules;

use Opscale\Rules\CLEAN\Representation\RepresentationLayerRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(RepresentationLayerRule::class)]
class RepresentationLayerTest extends RuleTestCase
{
    #[Test]
    public function detects_layer_violations(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Models/User.php',
        ], [
            [
                'Clean Architecture violation: Class "Opscale\Models\User" from layer 1 cannot depend on "Opscale\Jobs\CleanOldProducts" from layer 4. ' .
                'Layers can only use equal or lower layers and communicate via events upwards.',
                8,
            ],
            [
                'Clean Architecture violation: Class "Opscale\Models\User" from layer 1 cannot depend on "Illuminate\Support\Facades\Storage". ' .
                'This import is not allowed in this layer according to facade, framework, project, or external import rules.',
                9,
            ],
            [
                'Clean Architecture violation: Class "Opscale\Models\User" from layer 1 cannot depend on "Illuminate\Support\Str". ' .
                'This import is not allowed in this layer according to facade, framework, project, or external import rules.',
                10,
            ],
            [
                'Clean Architecture violation: Class "Opscale\Models\User" from layer 1 cannot depend on "Illuminate\Http\Request". ' .
                'This import is not allowed in this layer according to facade, framework, project, or external import rules.',
                11,
            ],
        ]);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new RepresentationLayerRule($reflectionProvider);
    }
}
