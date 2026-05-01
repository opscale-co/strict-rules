<?php

declare(strict_types=1);

namespace Opscale\Rules\DDD\Repositories;

use Opscale\Rules\BaseRule;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that restricts Eloquent method calls to classes inside
 * `\Models\Repositories\*` or `\Services\*`. Models themselves, Controllers,
 * Jobs, Listeners, Observers, Nova classes, Console commands and any
 * other class that does not live under those two namespaces must not
 * call Eloquent methods directly — they must delegate to a Repository
 * trait or to a Service / Action class.
 *
 * Detection mechanics:
 *   - A `StaticCall` whose class is an Eloquent Model FQCN (User::find)
 *     and whose method is in the curated Eloquent-method list is always
 *     flagged.
 *   - A `StaticCall` to `self`, `static`, or `parent` is only flagged
 *     when the enclosing class is an Eloquent Model — this prevents
 *     false positives on non-Eloquent classes that happen to declare
 *     methods with the same names (`find`, `get`, `clone`, ...).
 *   - A `MethodCall` on `$this` is only flagged when the enclosing
 *     class is an Eloquent Model.
 */
class EloquentRestrictionRule extends BaseRule
{
    private const ALLOWED_NAMESPACES = ['\\Models\\Repositories', '\\Services'];

    private const ERROR_TEMPLATE = 'Eloquent calls are only allowed within '.
        '`\\Models\\Repositories\\*` or `\\Services\\*`. Found "%s" call in "%s".';

    protected function shouldProcess(Node $node, Scope $scope): bool
    {
        if (parent::shouldProcess($node, $scope) === false) {
            return false;
        }

        assert($node instanceof \PHPStan\Node\FileNode);
        $rootNode = $this->getRootNode($node);
        if ($rootNode instanceof Enum_) {
            return false;
        }

        $namespace = $this->getNamespace($node);
        if ($this->isInNamespaces($namespace, self::ALLOWED_NAMESPACES)) {
            return false;
        }

        return true;
    }

    protected function validate(Node $node): array
    {
        assert($node instanceof \PHPStan\Node\FileNode);
        $rootNode = $this->getRootNode($node);
        if ($rootNode === null) {
            return [];
        }

        $errors = [];
        $nodeFinder = new NodeFinder;
        $methods = $this->getMethodNodes($rootNode);

        foreach ($methods as $method) {
            $calls = $nodeFinder->findInstanceOf($method->stmts ?? [], Node\Expr::class);
            foreach ($calls as $call) {
                if (! $this->isEloquentCall($call, $rootNode)) {
                    continue;
                }

                $methodName = $this->callMethodName($call);
                $errors[] = RuleErrorBuilder::message(sprintf(
                    self::ERROR_TEMPLATE,
                    $methodName,
                    $rootNode->namespacedName?->toString() ?? 'Unknown'
                ))
                    ->line($call->getLine())
                    ->identifier('ddd.repositories.eloquentRestriction')
                    ->build();
            }
        }

        return $errors;
    }

    private function isEloquentCall(Node $node, Class_|Trait_|Enum_|null $rootNode): bool
    {
        if ($this->isDirectModelStaticCall($node)) {
            return true;
        }

        if ($this->isSelfStaticCallInModel($node, $rootNode)) {
            return true;
        }

        return $this->isThisCallInModel($node, $rootNode);
    }

    /**
     * Static call on an Eloquent Model FQCN — flagged regardless of the
     * enclosing class (e.g., `User::find($id)` from a Controller).
     */
    private function isDirectModelStaticCall(Node $node): bool
    {
        if (! $node instanceof StaticCall || ! $node->class instanceof Name) {
            return false;
        }

        $className = $node->class->toString();
        if (in_array($className, ['self', 'static', 'parent'], true)) {
            return false;
        }

        if (! $this->isEloquentModelClassName($className)) {
            return false;
        }

        $methodName = $node->name instanceof Identifier ? $node->name->toString() : null;

        return $methodName !== null && in_array($methodName, $this->getEloquentMethods(), true);
    }

    /**
     * `self::method()` / `static::method()` / `parent::method()` — only
     * flagged when the enclosing class is itself an Eloquent Model.
     */
    private function isSelfStaticCallInModel(Node $node, Class_|Trait_|Enum_|null $rootNode): bool
    {
        if (! $node instanceof StaticCall || ! $node->class instanceof Name) {
            return false;
        }

        if (! in_array($node->class->toString(), ['self', 'static', 'parent'], true)) {
            return false;
        }

        $methodName = $node->name instanceof Identifier ? $node->name->toString() : null;
        if ($methodName === null || ! in_array($methodName, $this->getEloquentMethods(), true)) {
            return false;
        }

        return $this->isEnclosingClassAModel($rootNode);
    }

    /**
     * `$this->method(...)` — only flagged when the enclosing class is
     * itself an Eloquent Model.
     */
    private function isThisCallInModel(Node $node, Class_|Trait_|Enum_|null $rootNode): bool
    {
        if (! $node instanceof MethodCall) {
            return false;
        }

        if (! $node->var instanceof Node\Expr\Variable || $node->var->name !== 'this') {
            return false;
        }

        $methodName = $node->name instanceof Identifier ? $node->name->toString() : null;
        if ($methodName === null || ! in_array($methodName, $this->getEloquentMethods(), true)) {
            return false;
        }

        return $this->isEnclosingClassAModel($rootNode);
    }

    private function isEnclosingClassAModel(Class_|Trait_|Enum_|null $rootNode): bool
    {
        if (! $rootNode instanceof Class_ || ! $rootNode->namespacedName instanceof Name) {
            return false;
        }

        return $this->isEloquentModelClassName($rootNode->namespacedName->toString());
    }

    private function isEloquentModelClassName(string $className): bool
    {
        if (! $this->reflectionProvider->hasClass($className)) {
            return false;
        }

        $reflection = $this->reflectionProvider->getClass($className);

        if ($reflection->getName() === \Illuminate\Database\Eloquent\Model::class) {
            return true;
        }

        return $reflection->isSubclassOf(\Illuminate\Database\Eloquent\Model::class);
    }

    private function callMethodName(Node $call): string
    {
        if (property_exists($call, 'name') && $call->name instanceof Identifier) {
            return $call->name->toString();
        }

        return 'unknown';
    }

    /**
     * @return array<int, string>
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
            'trashed',

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
            'orWhereRaw', 'orHavingRaw',
        ];
    }
}
