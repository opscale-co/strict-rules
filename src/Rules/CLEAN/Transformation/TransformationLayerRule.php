<?php

namespace Opscale\Rules\CLEAN\Transformation;

use Opscale\Rules\CLEAN\CleanRule;
use PHPStan\Reflection\ReflectionProvider;

/**
 * Rule that enforces Clean Architecture for Transformation layer
 * Allows usage of commonly used framework imports and their subclasses
 */
class TransformationLayerRule extends CleanRule
{
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        parent::__construct(
            $reflectionProvider,
            3, // Transformation layer
            [ // Allowed framework imports
                'Illuminate\\Contracts\\',
                'Illuminate\\Foundation\\',
                'Symfony\\Component\\',
                'Illuminate\\Http\\Client\\',
            ],
            [ // Allowed facades
                'App',
                'Cache',
                'Config',
                'Crypt',
                'Exceptions',
                'File',
                'Http',
                'Storage',
            ],
            [ // Allowed external imports
                'Carbon\\',
                'Lorisleiva\\',
                'Ramsey\\Uuid\\',
                'Spatie\\',
            ]
        );
    }
}
