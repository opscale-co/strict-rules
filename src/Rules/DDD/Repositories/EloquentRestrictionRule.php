<?php

namespace Opscale\Rules\DDD\Repositories;

use Illuminate\Database\Eloquent\Model;
use Opscale\Rules\DDD\DomainRule;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
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
                // Check if trait is in any of the allowed namespaces
                if ($rootNode instanceof Trait_ &&
                    $this->isInNamespaces(
                        $namespace,
                        [self::REPOSITORIES_NAMESPACE])) {
                    continue;
                }

                $methodName = 'unknown';
                if (property_exists($call, 'name') && $call->name !== null) {
                    $methodName = $call->name instanceof Identifier ?
                        $call->name->toString() : 'unknown';
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
    private function isEloquentQueryBuilderCall(Node $node, Class_|Trait_|null $rootNode): bool
    {
        if ($this->isStaticEloquentCall($node)) {
            return true;
        }
        if ($this->isDirectModelClassCall($node)) {
            return true;
        }

        return $this->isThisMethodCall($node, $rootNode);
    }

    /**
     * Check for static calls on self:: or static::
     */
    private function isStaticEloquentCall(Node $node): bool
    {
        if (! ($node instanceof StaticCall && $node->class instanceof Name)) {
            return false;
        }

        $className = $node->class->toString();
        if (! in_array($className, ['self', 'static', 'parent'])) {
            return false;
        }

        $methodName = $node->name instanceof Identifier ?
            $node->name->toString() : null;

        return $methodName &&
            in_array($methodName, $this->getQueryBuilderMethods());
    }

    /**
     * Check for direct model class calls
     */
    private function isDirectModelClassCall(Node $node): bool
    {
        if (! ($node instanceof StaticCall && $node->class instanceof Name)) {
            return false;
        }

        $className = $node->class->toString();
        if (! $this->isEloquentModel($className)) {
            return false;
        }

        $methodName = $node->name instanceof Node\Identifier ?
            $node->name->toString() : null;

        return $methodName &&
            in_array($methodName, $this->getQueryBuilderMethods());
    }

    /**
     * Check for method calls on $this
     */
    private function isThisMethodCall(Node $node, Class_|Trait_|null $rootNode): bool
    {
        if (! ($node instanceof MethodCall &&
            ($node->var instanceof Node\Expr\Variable &&
            $node->var->name === 'this'))) {
            return false;
        }

        $methodName = $node->name instanceof Node\Identifier ?
            $node->name->toString() : null;
        if (! $methodName ||
            ! in_array($methodName, $this->getQueryBuilderMethods())) {
            return false;
        }

        if ($rootNode instanceof Class_ && $rootNode->namespacedName) {
            $namespace = $rootNode->namespacedName->toString();

            return $this->isEloquentModel($namespace);
        }

        return false;
    }

    /**
     * Get the list of Eloquent query builder methods
     */
    private function getQueryBuilderMethods(): array
    {
        return [
            'where', 'whereHas', 'whereIn', 'whereNotIn', 'whereBetween',
            'orWhere', 'orderBy', 'groupBy', 'having', 'join', 'leftJoin',
            'first', 'find', 'findOrFail', 'get', 'all', 'paginate',
            'exists', 'count', 'sum', 'avg', 'max', 'min',
            'create', 'update', 'delete', 'save', 'fill',
            'with', 'load', 'latest', 'oldest', 'limit', 'take', 'skip',
            'select', 'distinct', 'pluck', 'chunk', 'each',
        ];
    }
}
