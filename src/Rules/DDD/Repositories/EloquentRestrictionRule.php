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
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeFinder;
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
    private const REPOSITORIES_NAMESPACE = '\\Models\\Repositories';

    protected function validate(Node $node): array
    {
        assert($node instanceof \PHPStan\Node\FileNode);
        $errors = [];
        $rootNode = $this->getRootNode($node);
        if ($rootNode === null) {
            return [];
        }

        $nodeFinder = new NodeFinder;
        $methods = $this->getMethodNodes($rootNode);

        foreach ($methods as $method) {
            $calls = $nodeFinder->findInstanceOf($method->stmts ?? [], Node\Expr::class);
            foreach ($calls as $call) {
                // Check if we're making an Eloquent query builder call
                if (! $this->isEloquentQueryBuilderCall($call, $rootNode)) {
                    continue;
                }

                $namespace = $rootNode->namespacedName?->toString() ?? 'Unknown';

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
    private function isEloquentQueryBuilderCall(Node $node, Class_|Trait_|Enum_|null $rootNode): bool
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
            in_array($methodName, $this->getEloquentMethods());
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
            in_array($methodName, $this->getEloquentMethods());
    }

    /**
     * Check for method calls on $this
     */
    private function isThisMethodCall(Node $node, Class_|Trait_|Enum_|null $rootNode): bool
    {
        if (! ($node instanceof MethodCall &&
            ($node->var instanceof Node\Expr\Variable &&
            $node->var->name === 'this'))) {
            return false;
        }

        $methodName = $node->name instanceof Node\Identifier ?
            $node->name->toString() : null;
        if (! $methodName ||
            ! in_array($methodName, $this->getEloquentMethods())) {
            return false;
        }

        if ($rootNode instanceof Class_ && $rootNode->namespacedName) {
            $namespace = $rootNode->namespacedName->toString();

            return $this->isEloquentModel($namespace);
        }

        return false;
    }

    /**
     * Get the list of Eloquent methods
     */
    private function getEloquentMethods(): array
    {
        return [
            // Query builder methods
            'where', 'whereHas', 'whereIn', 'whereNotIn', 'whereBetween',
            'whereNull', 'whereNotNull', 'whereExists', 'whereNotExists',
            'whereColumn', 'whereRaw', 'whereJsonContains', 'whereJsonLength',
            'orWhere', 'orWhereHas', 'orWhereIn', 'orWhereNotIn', 'orWhereBetween',
            'orWhereNull', 'orWhereNotNull', 'orWhereExists', 'orWhereNotExists',

            // Ordering and grouping
            'orderBy', 'orderByDesc', 'orderByRaw', 'latest', 'oldest',
            'inRandomOrder', 'groupBy', 'groupByRaw', 'having', 'havingRaw',

            // Joins
            'join', 'leftJoin', 'rightJoin', 'crossJoin',
            'joinSub', 'leftJoinSub', 'rightJoinSub',

            // Retrieval methods
            'get', 'first', 'firstOrFail', 'firstOr', 'firstWhere',
            'find', 'findOrFail', 'findOr', 'findMany',
            'findOrNew', 'firstOrNew', 'firstOrCreate',
            'all', 'value', 'pluck', 'sole',

            // Pagination
            'paginate', 'simplePaginate', 'cursorPaginate',

            // Aggregate methods
            'count', 'sum', 'avg', 'average', 'min', 'max',
            'exists', 'doesntExist',

            // Modification methods - CRUD operations
            'create', 'insert', 'insertOrIgnore', 'insertGetId', 'insertUsing',
            'update', 'updateOrFail', 'updateOrCreate', 'updateOrInsert',
            'upsert', 'increment', 'decrement',
            'delete', 'destroy', 'forceDelete', 'restore',
            'save', 'saveOrFail', 'saveQuietly',
            'fill', 'forceFill', 'fillable', 'guarded',

            // Soft deletes
            'withTrashed', 'onlyTrashed', 'withoutTrashed',
            'trashed', 'restore', 'forceDelete',

            // Relationship methods
            'with', 'withCount', 'withSum', 'withAvg', 'withMin', 'withMax',
            'withExists', 'without', 'withOnly',
            'load', 'loadCount', 'loadSum', 'loadAvg', 'loadMin', 'loadMax',
            'loadMissing', 'loadMorph', 'loadAggregate',
            'belongsTo', 'hasOne', 'hasMany', 'hasManyThrough',
            'belongsToMany', 'morphTo', 'morphOne', 'morphMany',
            'morphToMany', 'morphedByMany',

            // Scopes and constraints
            'limit', 'take', 'skip', 'offset', 'forPage',
            'select', 'selectRaw', 'selectSub', 'addSelect',
            'distinct', 'from', 'fromRaw', 'fromSub',

            // Collection operations
            'chunk', 'chunkById', 'each', 'eachById',
            'lazy', 'lazyById', 'lazyByIdDesc', 'cursor',

            // Model state methods
            'getAttribute', 'setAttribute', 'getAttributes', 'setAttributes',
            'getOriginal', 'only', 'except', 'syncOriginal',
            'makeVisible', 'makeHidden', 'append', 'setAppends',
            'getVisible', 'getHidden', 'getFillable', 'getGuarded',

            // Model utility methods
            'fresh', 'refresh', 'replicate', 'is', 'isNot',
            'getKey', 'getKeyName', 'getKeyType', 'getRouteKey', 'getRouteKeyName',
            'getMorphClass', 'getTable', 'getConnection', 'getConnectionName',

            // Timestamps
            'touch', 'touchQuietly', 'updateTimestamps', 'usesTimestamps',
            'getCreatedAtColumn', 'getUpdatedAtColumn',

            // Events
            'observe', 'setObservableEvents', 'getObservableEvents',

            // Other common methods
            'toArray', 'toJson', 'jsonSerialize', 'toSql', 'dd', 'dump',
            'clone', 'newInstance', 'newFromBuilder', 'newQuery', 'newModelQuery',
            'wasRecentlyCreated', 'wasChanged', 'isDirty', 'isClean',
            'push', 'pushQuietly',

            // Mass assignment
            'unguard', 'reguard', 'isGuarded', 'isFillable',
            'totallyGuarded', 'fillableFromArray',

            // Query scopes
            'withGlobalScope', 'withoutGlobalScope', 'withoutGlobalScopes',
            'removedScopes', 'appliedScopes',

            // Locking
            'lockForUpdate', 'sharedLock',

            // Raw expressions
            'whereRaw', 'orWhereRaw', 'havingRaw', 'orHavingRaw',
            'orderByRaw', 'groupByRaw', 'selectRaw', 'fromRaw',
        ];
    }
}
