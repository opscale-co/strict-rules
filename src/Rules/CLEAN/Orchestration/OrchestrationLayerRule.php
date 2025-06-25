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
        parent::__construct($reflectionProvider, 4, [
            'Illuminate\Bus',
            'Illuminate\Contracts',
            'Illuminate\Foundation\Bus',
            'Illuminate\Notifications',
            'Illuminate\Queue',
        ]);
    }
}
