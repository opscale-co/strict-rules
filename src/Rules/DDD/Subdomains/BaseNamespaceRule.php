<?php

namespace Opscale\Rules\DDD\Subdomains;

use Opscale\Rules\DDD\DomainRule;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that ensures Eloquent models are in the correct namespace
 */
class BaseNamespaceRule extends DomainRule
{
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        parent::__construct($reflectionProvider);
    }

    public function processNode(Node $node, Scope $scope): array
    {
        // @phpstan-ignore-next-line
        if (! ($node instanceof FileNode) ||
            ! $this->isEloquentModel($node)) {
            return []; // Skip if not a model class
        }

        $errors = [];

        // Check if the class is in the root\Models namespace
        $pattern = '/^(\w+\\\\){1,2}(' . self::MODELS_NAMESPACE . ')/';
        $namespace = $this->getNamespace($node);
        if (preg_match($pattern, $namespace)) {
            return [];
        }

        $rootNode = $this->getRootNode($node);
        $error = sprintf(
            'Class "%s" extends Eloquent Model but is not in the "root\Models" namespace. ' .
            'Eloquent models must be in the "root\Models" namespace.',
            $rootNode ? $rootNode->namespacedName->toString() : 'Unknown',
        );

        $namespaceNode = $this->getNamespaceNode($node);
        $errors[] = RuleErrorBuilder::message($error)
            ->line($namespaceNode instanceof \PhpParser\Node\Stmt\Namespace_ ? $namespaceNode->getLine() : 1)
            ->identifier('ddd.subdomains.baseNamespace')
            ->build();

        return $errors;
    }
}
