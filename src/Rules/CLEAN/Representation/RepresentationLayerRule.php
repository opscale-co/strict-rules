<?php

namespace Opscale\Rules\CLEAN\Representation;

use Opscale\Rules\CLEAN\CleanRule;
use PhpParser\Node;
use PhpParser\Node\UseItem;
use PHPStan\Reflection\ReflectionProvider;

/**
 * Rule that enforces Clean Architecture for Representation layer
 * Allows usage of commonly used framework imports and their subclasses
 */
class RepresentationLayerRule extends CleanRule
{
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        parent::__construct(
            $reflectionProvider,
            1, // Representation layer
            [ // Allowed framework imports
                'Illuminate\\Database\\',
            ],
            [ // Allowed facades
                'DB',
                'Hash',
                'Schema',
            ],
            [ // Allowed external imports
                'Carbon\\',
                'Spatie\\',
            ]
        );
    }

    /**
     * Override to allow trait imports in Eloquent models in the Representation layer
     */
    public function isAllowedUse(Node $fileNode, UseItem $useItem): bool
    {
        assert($fileNode instanceof \PHPStan\Node\FileNode);
        $usedClass = $useItem->name->toString();
        $rootNode = $this->getRootNode($fileNode);
        $currentClassName = $rootNode?->namespacedName->toString();

        // Check if the current file is an Eloquent model and the imported class is a trait or Eloquent model
        if ($currentClassName && $this->isEloquentModel($currentClassName) &&
            $this->reflectionProvider->hasClass($usedClass)) {
            $reflection = $this->reflectionProvider->getClass($usedClass);
            if ($reflection->isTrait() || $this->isEloquentModel($usedClass)) {
                return true; // Automatically approve trait and Eloquent model imports in Eloquent models
            }
        }

        // Fall back to parent logic for non-trait/non-model imports
        return parent::isAllowedUse($fileNode, $useItem);
    }

    /**
     * Check if the given class name represents an Eloquent model
     */
    private function isEloquentModel(string $className): bool
    {
        if ($this->reflectionProvider->hasClass($className)) {
            $reflection = $this->reflectionProvider->getClass($className);

            return $reflection->isSubclassOf(\Illuminate\Database\Eloquent\Model::class);
        }

        return false;
    }
}
