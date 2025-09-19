<?php

namespace Opscale\Rules\DDD\Subdomains;

use Opscale\Rules\DDD\DomainRule;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that ensures Eloquent models are in the correct namespace
 */
class BaseNamespaceRule extends DomainRule
{
    protected function shouldProcess(Node $node, Scope $scope): bool
    {
        // @phpstan-ignore-next-line
        if (! ($node instanceof FileNode)) {
            return false;
        }

        $classReflection = $this->getClassReflection($node);
        if (! $classReflection ||
            $classReflection->isAnonymous() ||
            $classReflection->isInterface() ||
            $classReflection->isTrait() ||
            $classReflection->isEnum() ||
            ! $this->isEloquentModel($node)) {
            return false;
        }

        return true;
    }

    protected function validate(Node $node): array
    {
        assert($node instanceof \PHPStan\Node\FileNode);
        $errors = [];

        // Check if the class is in the correct \Models namespace
        $namespace = $this->getNamespace($node);
        if (str_ends_with($namespace, '\\Models')) {
            return []; // Class is in correct namespace, no error
        }

        $rootNode = $this->getRootNode($node);
        $error = sprintf(
            'Class "%s" extends Eloquent Model but is not in the "root\Models" namespace. ' .
            'Eloquent models must be in the "root\Models" namespace.',
            $rootNode?->namespacedName?->toString() ?? 'Unknown',
        );

        $namespaceNode = $this->getNamespaceNode($node);
        $errors[] = RuleErrorBuilder::message($error)
            ->line($namespaceNode instanceof \PhpParser\Node\Stmt\Namespace_ ? $namespaceNode->getLine() : 1)
            ->identifier('ddd.subdomains.baseNamespace')
            ->build();

        return $errors;
    }
}
