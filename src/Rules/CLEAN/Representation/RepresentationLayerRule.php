<?php

namespace Opscale\Rules\CLEAN\Representation;

use Opscale\Rules\CLEAN\CleanRule;
use PHPStan\Reflection\ReflectionProvider;

/**
 * Rule that enforces Clean Architecture for Representation layer
 * Allows usage of commonly used base classes and their subclasses
 */
class RepresentationLayerRule extends CleanRule
{
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        parent::__construct($reflectionProvider, 1, [
            'Illuminate\Database',   // Eloquent ORM related classes
            'Illuminate\Validation',  // Validation related classes
        ]);
    }
}
