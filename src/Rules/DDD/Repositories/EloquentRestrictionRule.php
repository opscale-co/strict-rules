<?php

namespace Opscale\Rules\DDD\Repositories;

use Illuminate\Database\Eloquent\Model;
use Opscale\Rules\DDD\DomainRule;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that restricts Eloquent model method calls within model classes themselves,
 * but allows them within Traits in the Models\Repositories or Domain\Services namespaces
 */
class EloquentRestrictionRule extends DomainRule
{
    /**
     * Target namespace for repositories
     */
    private const REPOSITORIES_NAMESPACE = '\\Models\\Repositories\\';

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        parent::__construct($reflectionProvider);
    }

    public function processNode(Node $node, Scope $scope): array
    {
        // @phpstan-ignore-next-line
        if (! ($node instanceof FileNode)) {
            return [];
        }

        $errors = [];
        $rootNode = $this->getRootNode($node);
        $nodeFinder = new NodeFinder;
        $methods = $this->getMethodNodes($rootNode);

        foreach ($methods as $method) {
            $calls = $nodeFinder->findInstanceOf($method->stmts ?? [], Node\Expr::class);
            foreach ($calls as $call) {
                // Check if we're making an Eloquent query builder call
                if (! $this->isEloquentQueryBuilderCall($call, $rootNode)) {
                    continue;
                }

                $namespace = $rootNode->namespacedName->toString();

                // If we're in a trait, check if it's in an allowed namespace
                if ($rootNode instanceof Trait_) {
                    // Check if trait is in any of the allowed namespaces
                    if ($this->isInNamespaces($namespace, [self::REPOSITORIES_NAMESPACE])) {
                        continue;
                    }
                }

                $methodName = 'unknown';
                if (isset($call->name)) {
                    $methodName = $call->name instanceof Identifier ? $call->name->toString() : 'unknown';
                }

                $error = sprintf(
                    'Eloquent calls are only allowed within ' .
                    'Repositories: Found "%s" call in "%s".',
                    $methodName,
                    $namespace
                );

                $errors[] = RuleErrorBuilder::message($error)
                    ->line($call->getLine())
                    ->identifier('ddd.repositories.eloquentRestriction')
                    ->build();
            }
        }

        return $errors;
    }

    /**
     * Check if the node represents an Eloquent query builder call
     */
    private function isEloquentQueryBuilderCall(Node $node, Node $rootNode): bool
    {
        // Common Eloquent query builder methods
        $queryBuilderMethods = [
            'where', 'whereHas', 'whereIn', 'whereNotIn', 'whereBetween',
            'orWhere', 'orderBy', 'groupBy', 'having', 'join', 'leftJoin',
            'first', 'find', 'findOrFail', 'get', 'all', 'paginate',
            'exists', 'count', 'sum', 'avg', 'max', 'min',
            'create', 'update', 'delete', 'save', 'fill',
            'with', 'load', 'latest', 'oldest', 'limit', 'take', 'skip',
            'select', 'distinct', 'pluck', 'chunk', 'each',
        ];

        // Check for static calls on self:: or static::
        if ($node instanceof StaticCall) {
            if ($node->class instanceof Name) {
                $className = $node->class->toString();
                if (in_array($className, ['self', 'static', 'parent'])) {
                    $methodName = $node->name instanceof Identifier ? $node->name->toString() : null;
                    if ($methodName && in_array($methodName, $queryBuilderMethods)) {
                        return true;
                    }
                }

                // Also check for direct model class calls
                if ($this->isEloquentModel($className)) {
                    $methodName = $node->name instanceof Node\Identifier ? $node->name->toString() : null;
                    if ($methodName && in_array($methodName, $queryBuilderMethods)) {
                        return true;
                    }
                }
            }
        }

        // Check for method calls on $this
        if ($node instanceof MethodCall) {
            // Check if it's a call on $this
            if ($node->var instanceof Node\Expr\Variable && $node->var->name === 'this') {
                $methodName = $node->name instanceof Node\Identifier ? $node->name->toString() : null;
                if ($methodName && in_array($methodName, $queryBuilderMethods)) {
                    // Verify that $this is an Eloquent model by checking the root node's namespace
                    if ($rootNode instanceof \PhpParser\Node\Stmt\Class_ && $rootNode->namespacedName) {
                        $namespace = $rootNode->namespacedName->toString();

                        return $this->isEloquentModel($namespace);
                    }

                    return false;
                }
            }
        }

        return false;
    }
}
