<?php

namespace Opscale\Rules\CLEAN\Orchestration;

use Opscale\Rules\CLEAN\CleanRule;
use PHPStan\Reflection\ReflectionProvider;

/**
 * Rule that enforces Clean Architecture for Orchestration layer
 * Allows usage of commonly used framework imports and their subclasses
 */
class OrchestrationLayerRule extends CleanRule
{
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        parent::__construct(
            $reflectionProvider,
            4, // Orchestration layer
            [ // Allowed framework imports
                'Illuminate\\Bus\\',
                'Illuminate\\Contracts\\Queue\\',
                'Illuminate\\Foundation\\Bus\\',
                'Illuminate\\Mail\\',
                'Illuminate\\Notifications\\',
                'Illuminate\\Queue\\',
            ],
            [ // Allowed facades
                'Bus',
                'Concurrency',
                'Mail',
                'Notification',
                'Pipeline',
                'Queue',
                'Redis',
                'Schedule',
            ],
            [] // Allowed external imports
        );
    }
}
