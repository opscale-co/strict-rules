<?php

namespace Opscale\Rules\DDD;

use Illuminate\Database\Eloquent\Model;
use Opscale\Rules\BaseRule;
use PhpParser\Node;
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
    protected const MODELS_NAMESPACE = 'Models';

    /**
     * Target namespace for Domain models
     */
    protected const DOMAIN_NAMESPACE = 'Domain';

    protected function shouldProcess(Node $node, Scope $scope): bool
    {
        // @phpstan-ignore-next-line
        if (! $node instanceof FileNode ||
            parent::shouldProcess($node, $scope) === false) {
            return false;
        }

        // Check if the class is in the Models or Domain namespace
        $className = $this->getRootNode($node)->namespacedName->toString();
        $modelsPattern = '/^(\w+\\\\){1,2}(' . self::MODELS_NAMESPACE . ')/';
        $domainPattern = '/^(\w+\\\\){1,2}(' . self::DOMAIN_NAMESPACE . ')/';

        if (preg_match($modelsPattern, $className) === false &&
            preg_match($domainPattern, $className) === false) {
            return false;
        }

        return true;
    }

    /**
     * Check if the class extends Eloquent Model
     */
    protected function isEloquentModel(FileNode|string $class): bool
    {
        $classReflection = is_string($class) ?
            $this->reflectionProvider->getClass($class) :
            $this->getClassReflection($class);
        if (! $classReflection instanceof \PHPStan\Reflection\ClassReflection) {
            return false;
        }

        if ($classReflection->getName() === Model::class) {
            return true;
        }

        return $classReflection->isSubclassOf(Model::class);
    }
}
