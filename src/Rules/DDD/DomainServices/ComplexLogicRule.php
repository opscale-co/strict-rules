<?php

namespace Opscale\Rules\DDD\DomainServices;

use Illuminate\Database\Eloquent\Model;
use Opscale\Rules\DDD\DomainRule;
use PhpParser\Node;
use PHPStan\Rules\RuleErrorBuilder;
use Throwable;

/**
 * Rule that verifies Service classes can have complex logic with multiple Eloquent models,
 * but other classes should limit their model dependencies
 */
class ComplexLogicRule extends DomainRule
{
    protected function validate(Node $node): array
    {
        assert($node instanceof \PHPStan\Node\FileNode);
        $errors = [];
        $rootNode = $this->getRootNode($node);
        if ($rootNode === null) {
            return [];
        }

        $namespace = $this->getNamespace($node);
        $isServiceClass = $this->isInNamespaces($namespace, ['\\Services']);
        $modelsCount = 0;

        // Get all use statements and count Eloquent models
        $uses = $this->getUseStatements($node);

        foreach ($uses as $use) {
            $usedClass = $use->name->toString();
            $modelsCount += $this->isEloquentModelClass($usedClass) ? 1 : 0;

            // Non-Service classes should limit their model dependencies
            if (! $isServiceClass && $modelsCount > 1) {
                $error = sprintf(
                    'Class "%s" is importing %d Eloquent models, and it should not import more than 1. ' .
                    'Consider moving complex logic involving multiple models to a Service class in the Services namespace.',
                    $rootNode->namespacedName?->toString() ?? 'Unknown',
                    $modelsCount
                );

                $errors[] = RuleErrorBuilder::message($error)
                    ->line($rootNode->getLine())
                    ->identifier('ddd.domainServices.complexLogic')
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
            if (! $this->reflectionProvider->hasClass($className)) {
                return false;
            }

            $classReflection = $this->reflectionProvider->getClass($className);
            if ($classReflection->getName() === Model::class) {
                return true;
            }

            return $classReflection->isSubclassOf(Model::class);

        } catch (Throwable $throwable) {
            // If we can't determine if it's a model, assume it's not
            return false;
        }
    }
}
