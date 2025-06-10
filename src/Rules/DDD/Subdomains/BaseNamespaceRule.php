<?php

namespace Opscale\Rules\DDD\Subdomains;

use Opscale\Rules\DDD\DomainRule;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that ensures Eloquent models are in the correct namespace
 */
class BaseNamespaceRule extends DomainRule
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
        if(!$this->isEloquentModel($node)) {
            return []; // Skip if not a model class
        }

        $errors = [];
        
        // Check if the class is in the root\Models namespace
        $pattern = '/^(\w+\\\\){1,2}(' . self::MODELS_NAMESPACE . ')/';
        $namespace = $this->getNamespace($node);
        if (preg_match($pattern, $namespace)) {
            return [];
        }
        
        $error = sprintf(
            'Class "%s" extends Eloquent Model but is not in the "root\Models" namespace. ' .
            'Eloquent models must be in the "root\Models" namespace.',
            $this->getRootNode($node)->namespacedName->toString(),
        );

        $errors[] = RuleErrorBuilder::message($error)
            ->line($this->getNamespaceNode($node)->getLine())
            ->build();
        
        return $errors;
    }
}