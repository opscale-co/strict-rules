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
 * Rule that restricts Eloquent CRUD method calls to classes inside
 * `\Models\Repositories\*` or `\Services\*`. Models themselves, Controllers,
 * Jobs, Listeners, Observers, Nova classes, Console commands and any
 * other class that does not live under those two namespaces must not
 * call Eloquent CRUD methods directly — they must delegate to a Repository
 * trait or to a Service / Action class.
 *
 * The curated method list covers ONLY CRUD operations: query building
 * (where, orderBy, joins, ...), retrieval (get, first, find, ...),
 * pagination, aggregates, persistence (create, update, delete, save, ...),
 * soft deletes, query constraints (limit, select, ...), locking and raw
 * expressions. Relationship declarations (`belongsTo`, `hasMany`, `with`,
 * `load`, ...), collection iteration (`chunk`, `cursor`, ...), model
 * state accessors (`getAttribute`, `fill`, ...), timestamp utilities,
 * event hooks and serialization helpers (`toArray`, `toJson`, ...) are
 * intentionally NOT flagged — they are legitimate Model concerns.
 *
 * Detection mechanics:
 *   - A `StaticCall` whose class is an Eloquent Model FQCN (User::find)
 *     and whose method is in the curated CRUD list is always flagged.
 *   - A `StaticCall` to `self`, `static`, or `parent` is only flagged
 *     when the enclosing class is an Eloquent Model — this prevents
 *     false positives on non-Eloquent classes that happen to declare
 *     methods with the same names (`find`, `get`, ...).
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
     * Curated list of Eloquent CRUD methods. Only operations that read
     * from or write to the database belong here. Relationship methods
     * (`belongsTo`, `with`, `load`, ...), collection iteration helpers
     * (`chunk`, `cursor`, ...), model state accessors, timestamp
     * utilities, events and serialization helpers are intentionally
     * excluded — they are legitimate Model concerns and must not be
     * confined to repositories or services.
     *
     * @return array<int, string>
     */
    private function getEloquentMethods(): array
    {
        return [
            // Query builder — where clauses
            'where', 'whereHas', 'whereIn', 'whereNotIn', 'whereBetween',
            'whereNull', 'whereNotNull', 'whereExists', 'whereNotExists',
            'whereColumn', 'whereRaw', 'whereJsonContains', 'whereJsonLength',
            'orWhere', 'orWhereHas', 'orWhereIn', 'orWhereNotIn', 'orWhereBetween',
            'orWhereNull', 'orWhereNotNull', 'orWhereExists', 'orWhereNotExists',

            // Query builder — ordering and grouping
            'orderBy', 'orderByDesc', 'orderByRaw', 'latest', 'oldest',
            'inRandomOrder', 'groupBy', 'groupByRaw', 'having', 'havingRaw',

            // Query builder — joins
            'join', 'leftJoin', 'rightJoin', 'crossJoin',
            'joinSub', 'leftJoinSub', 'rightJoinSub',

            // Query builder — constraints and projection
            'limit', 'take', 'skip', 'offset', 'forPage',
            'select', 'selectRaw', 'selectSub', 'addSelect',
            'distinct', 'from', 'fromRaw', 'fromSub',

            // Query builder — soft delete scopes
            'withTrashed', 'onlyTrashed', 'withoutTrashed',

            // Query builder — locking
            'lockForUpdate', 'sharedLock',

            // Query builder — raw expressions
            'orWhereRaw', 'orHavingRaw',

            // Read — retrieval
            'get', 'first', 'firstOrFail', 'firstOr', 'firstWhere',
            'find', 'findOrFail', 'findOr', 'findMany',
            'findOrNew', 'firstOrNew',
            'all', 'value', 'pluck', 'sole',

            // Read — pagination
            'paginate', 'simplePaginate', 'cursorPaginate',

            // Read — aggregates
            'count', 'sum', 'avg', 'average', 'min', 'max',
            'exists', 'doesntExist',

            // Create
            'create', 'insert', 'insertOrIgnore', 'insertGetId', 'insertUsing',
            'firstOrCreate',

            // Update
            'update', 'updateOrFail', 'updateOrCreate', 'updateOrInsert',
            'upsert', 'increment', 'decrement',
            'save', 'saveOrFail', 'saveQuietly',

            // Delete
            'delete', 'destroy', 'forceDelete', 'restore',
        ];
    }
}
