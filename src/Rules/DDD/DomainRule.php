<?php

namespace Opscale\Rules\DDD;

use Illuminate\Database\Eloquent\Model;
use Opscale\Rules\BaseRule;
use PhpParser\Node;
use PhpParser\Node\Stmt\Enum_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;

/**
 * Base rule that ensures processing only for Model classes
 */
abstract class DomainRule extends BaseRule
{
    /**
     * Target namespace for Eloquent models
     */
    protected const MODELS_NAMESPACE = '\\Models';

    protected function shouldProcess(Node $node, Scope $scope): bool
    {
        // @phpstan-ignore-next-line
        if (parent::shouldProcess($node, $scope) === false) {
            return false;
        }

        // Skip rule verification for enums
        assert($node instanceof \PHPStan\Node\FileNode);
        $rootNode = $this->getRootNode($node);
        if ($rootNode instanceof Enum_) {
            return false;
        }

        // Check if the class is in the Models namespace only
        if ($rootNode === null || ! $rootNode->namespacedName instanceof \PhpParser\Node\Name) {
            return false;
        }

        $className = $rootNode->namespacedName->toString();
        if (! $this->isInNamespaces($className, [self::MODELS_NAMESPACE])) {
            return false;
        }

        return true;
    }

    /**
     * Check if the class extends Eloquent Model (checks entire inheritance chain)
     */
    protected function isEloquentModel(FileNode|string $class): bool
    {
        $classReflection = is_string($class) ?
            $this->reflectionProvider->getClass($class) :
            $this->getClassReflection($class);
        if (! $classReflection instanceof \PHPStan\Reflection\ClassReflection) {
            return false;
        }

        // Check if it's the Model class itself
        if ($classReflection->getName() === Model::class) {
            return true;
        }

        // Use PHPStan's built-in method to check if it's a subclass of Model
        // This should handle the entire inheritance chain properly
        return $classReflection->isSubclassOf(Model::class);
    }
}
