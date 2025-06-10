<?php

namespace Opscale\Rules\CLEAN\Transformation;

use PHPStan\Reflection\ReflectionProvider;
use Opscale\Rules\CLEAN\CleanRule;

/**
 * Rule that enforces Clean Architecture for Transformation layer
 * Allows usage of commonly used base classes and their subclasses
 */
class TransformationLayerRule extends CleanRule
{
    /**
     * @param ReflectionProvider $reflectionProvider
     */
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        parent::__construct($reflectionProvider);
    }

    /**
     * Get the current processing layer
     */
    protected function processingLayer(): int
    {
        return 3;
    }

    /**
     * Get the allowed base classes for the Interaction layer
     * Any class that extends/implements these is allowed across layers
     */
    protected function getAllowedBaseClasses(): array
    {
        return [
            'Illuminate\Http\Client',
            'Lorisleiva'
        ];
    }
}