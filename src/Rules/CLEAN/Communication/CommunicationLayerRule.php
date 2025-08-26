<?php

namespace Opscale\Rules\CLEAN\Communication;

use Opscale\Rules\CLEAN\CleanRule;
use PHPStan\Reflection\ReflectionProvider;

/**
 * Rule that enforces Clean Architecture for Communication layer
 * Allows usage of commonly used framework imports and their subclasses
 */
class CommunicationLayerRule extends CleanRule
{
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        parent::__construct(
            $reflectionProvider,
            2, // Communication layer
            [ // Allowed framework imports
                'Illuminate\\Contracts\\Broadcasting\\',
                'Illuminate\\Events\\',
            ],
            [ // Allowed facades
                'Broadcast',
                'Event',
            ],
            [] // Allowed external imports
        );
    }
}
