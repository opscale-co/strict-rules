<?php

namespace Opscale\Rules\CLEAN\Interaction;

use Opscale\Rules\CLEAN\CleanRule;
use PHPStan\Reflection\ReflectionProvider;

/**
 * Rule that enforces Clean Architecture for Interaction layer
 * Allows usage of commonly used base classes and their subclasses
 */
class InteractionLayerRule extends CleanRule
{
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        parent::__construct($reflectionProvider, 5, [
            // Laravel Framework Controllers
            'Illuminate\Routing',
            // Console Commands
            'Illuminate\Console',
            'Symfony\Component\Console',
            // HTTP Components
            'Illuminate\Http',
            'Illuminate\Foundation\Http',
            // Authentication & Authorization
            'Illuminate\Auth',
            'Illuminate\Contracts',
            // Nova Components
            'Laravel\Nova',
            // Laravel Sanctum (API authentication)
            'Laravel\Sanctum',
            // Laravel Passport (OAuth)
            'Laravel\Passport',
            // Livewire Components (if used)
            'Livewire',
            // Inertia.js (if used)
            'Inertia',
        ]);
    }
}
