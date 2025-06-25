<?php

namespace Opscale\Rules\CLEAN\Transformation;

use Opscale\Rules\CLEAN\CleanRule;
use PHPStan\Reflection\ReflectionProvider;

/**
 * Rule that enforces Clean Architecture for Transformation layer
 * Allows usage of commonly used base classes and their subclasses
 */
class TransformationLayerRule extends CleanRule
{
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        parent::__construct($reflectionProvider, 3, [
            'Illuminate\Http\Client',
            'Lorisleiva',
        ]);
    }
}
