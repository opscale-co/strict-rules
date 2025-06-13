<?php

namespace Opscale\Rules\DDD\Aggregates;

use Opscale\Rules\DDD\DomainRule;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that verifies Eloquent Model classes implement a validate method with specific signature
 */
class ModelValidationRule extends DomainRule
{
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        parent::__construct($reflectionProvider);
    }

    public function processNode(Node $node, Scope $scope): array
    {
        // @phpstan-ignore-next-line
        if (! $node instanceof FileNode ||
            ! $this->shouldProcess($node, $scope)) {
            return [];
        }

        $errors = [];
        $rootNode = $this->getRootNode($node);
        $classReflection = $this->getClassReflection($node);
        $methods = $this->getMethodNodes($rootNode);

        foreach ($methods as $method) {
            if ($method->name->toString() === 'validate') {
                // Check if the method signature matches the required one
                $params = $method->getParams();
                if (count($params) === 1 && $params[0]->type && $params[0]->type->toString() === 'string') {
                    return []; // Valid method found, no errors
                }
            }
        }

        $error = sprintf(
            'Model class "%s" must implement a "validate(string $key): array"',
            $rootNode->namespacedName->toString()
        );

        $errors[] = RuleErrorBuilder::message($error)
            ->line($rootNode->getLine())
            ->identifier('ddd.aggregates.modelValidation')
            ->build();

        return $errors;
    }

    protected function shouldProcess(Node $node, Scope $scope): bool
    {
        // @phpstan-ignore-next-line
        if (! $node instanceof FileNode ||
            parent::shouldProcess($node, $scope) === false ||
            ! $this->isEloquentModel($node)) {
            return false;
        }

        return true;
    }
}
