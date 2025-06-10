<?php

namespace Opscale\Rules\DDD\DomainServices;

use Opscale\Rules\DDD\DomainRule;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use Illuminate\Database\Eloquent\Model;

/**
 * Rule that verifies Service classes can have complex logic with multiple Eloquent models,
 * but other classes should limit their model dependencies
 */
class ComplexLogicRule extends DomainRule
{
    /**
     * @param ReflectionProvider $reflectionProvider
     */
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        parent::__construct($reflectionProvider);
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->shouldProcess($node, $scope)) {
            return [];
        }

        $errors = [];
        $rootNode = $this->getRootNode($node);
        $namespace = $this->getNamespace($node);
        $isServiceClass = $this->isInNamespaces($namespace, ['\\Services']);
        $modelsCount = 0;
        
        // Get all use statements and count Eloquent models
        $uses = $this->getUseStatements($node);

        foreach ($uses as $useNode) {
            $usedClass = $useNode->name->toString();
            $modelsCount += $this->isEloquentModelClass($usedClass) ? 1 : 0;

            // Non-Service classes should limit their model dependencies
            if (!$isServiceClass && $modelsCount > 1) {
                $error = sprintf(
                    'Class "%s" is importing %d Eloquent models, and it should not import more than 1. ' .
                    'Consider moving complex logic involving multiple models to a Service class in the Services namespace.',
                    $rootNode->namespacedName->toString(),
                    $modelsCount
                );

                $errors[] = RuleErrorBuilder::message($error)
                    ->line($rootNode->getLine())
                    ->build();

                break;
            }

        }

        return $errors;
    }

    /**
     * Check if a class is an Eloquent model
     */
    private function isEloquentModelClass(string $className): bool
    {
        try {
            if (!$this->reflectionProvider->hasClass($className)) {
                return false;
            }

            $classReflection = $this->reflectionProvider->getClass($className);
            
            // Check if the class extends Eloquent Model
            return $classReflection->getName() === Model::class
                || $classReflection->isSubclassOf(Model::class);
                
        } catch (\Throwable $e) {
            // If we can't determine if it's a model, assume it's not
            return false;
        }
    }
}