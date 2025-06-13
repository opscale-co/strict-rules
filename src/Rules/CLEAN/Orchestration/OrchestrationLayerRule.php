<?php

namespace Opscale\Rules\CLEAN\Orchestration;

use Opscale\Rules\CLEAN\CleanRule;
use PHPStan\Reflection\ReflectionProvider;

/**
 * Rule that enforces Clean Architecture for Orchestration layer
 * Allows usage of commonly used base classes and their subclasses
 */
class OrchestrationLayerRule extends CleanRule
{
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        parent::__construct($reflectionProvider);
    }

    /**
     * Get the current processing layer
     */
    protected function processingLayer(): int
    {
        return 4;
    }

    /**
     * Get the allowed base classes for the Interaction layer
     * Any class that extends/implements these is allowed across layers
     */
    protected function getAllowedBaseClasses(): array
    {
        return [
            'Illuminate\Bus',
            'Illuminate\Contracts',
            'Illuminate\Foundation\Bus',
            'Illuminate\Notifications',
            'Illuminate\Queue',
        ];
    }
}
