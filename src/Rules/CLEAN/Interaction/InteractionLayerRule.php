<?php

namespace Opscale\Rules\CLEAN\Interaction;

use Opscale\Rules\CLEAN\CleanRule;
use PHPStan\Reflection\ReflectionProvider;

/**
 * Rule that enforces Clean Architecture for Interaction layer
 * Allows usage of commonly used framework imports and their subclasses
 */
class InteractionLayerRule extends CleanRule
{
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        parent::__construct(
            $reflectionProvider,
            5, // Interaction layer
            [ // Allowed framework imports
                'Illuminate\\Console\\',
                'Illuminate\\Http\\',
                'Illuminate\\Routing\\',
                'Illuminate\\Foundation\\',
                'Illuminate\\Validation\\',
                'Symfony\\Component\\HttpFoundation\\',
            ],
            [ // Allowed facades
                'Artisan',
                'Auth',
                'Blade',
                'Context',
                'Cookie',
                'Gate',
                'Lang',
                'Password',
                'Process',
                'RateLimiter',
                'Redirect',
                'Request',
                'Response',
                'Route',
                'Session',
                'URL',
                'Validator',
                'View',
                'Vite',
            ],
            [] // Allowed external imports
        );
    }
}
