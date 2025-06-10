<?php

namespace Opscale\Rules\CLEAN\Interaction;

use PHPStan\Reflection\ReflectionProvider;
use Opscale\Rules\CLEAN\CleanRule;

/**
 * Rule that enforces Clean Architecture for Interaction layer
 * Allows usage of commonly used base classes and their subclasses
 */
class InteractionLayerRule extends CleanRule
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
        return 5;
    }

    /**
     * Get the allowed base classes for the Interaction layer
     * Any class that extends/implements these is allowed across layers
     */
    protected function getAllowedBaseClasses(): array
    {
        return [
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
        ];
    }
}