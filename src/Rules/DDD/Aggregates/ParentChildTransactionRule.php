<?php

declare(strict_types=1);

namespace Opscale\Rules\DDD\Aggregates;

use Opscale\Rules\DDD\DomainRule;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that prevents direct save() calls on child entities — Eloquent models
 * whose own class or any ancestor declares a method with a BelongsTo (or
 * MorphTo) return type. Such entities must be persisted through their
 * aggregate root.
 *
 * Detection is return-type-only: the textual presence of a belongsTo() call
 * inside a method body is NOT a signal of a relationship — modern Laravel
 * idiom declares relations with a typed return.
 */
class ParentChildTransactionRule extends DomainRule
{
    /**
     * Return-type names recognised as a parent relationship.
     */
    private const PARENT_RELATION_TYPES = [
        'BelongsTo',
        'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
        'MorphTo',
        'Illuminate\\Database\\Eloquent\\Relations\\MorphTo',
    ];

    protected function shouldProcess(Node $node, Scope $scope): bool
    {
        if (parent::shouldProcess($node, $scope) === false) {
            return false;
        }

        assert($node instanceof \PHPStan\Node\FileNode);
        $namespace = $this->getNamespace($node);
        if (! $this->isInNamespaces($namespace, ['\\Models\\Repositories', '\\Services'])) {
            return false;
        }

        return true;
    }

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
                if (! ($call instanceof MethodCall)) {
                    continue;
                }

                if (! ($call->name instanceof Node\Identifier)) {
                    continue;
                }

                if ($call->name->toString() !== 'save') {
                    continue;
                }

                $callerType = $this->resolveCallerTypeFromParams($method, $call);
                if ($callerType === null) {
                    continue;
                }

                if (! $this->isEloquentModel($callerType)) {
                    continue;
                }

                if (! $this->modelHasParentInChain($callerType)) {
                    continue;
                }

                $error = sprintf(
                    'Direct save() on model "%s" is not allowed. '.
                    'Models with parent relationships (belongsTo) should only be saved through their parent aggregates.',
                    $callerType
                );
                $errors[] = RuleErrorBuilder::message($error)
                    ->line($call->getLine())
                    ->identifier('ddd.aggregates.parentChildTransaction')
                    ->build();
            }
        }

        return $errors;
    }

    /**
     * Resolve the static type of the variable that the save() call is made on,
     * by matching it against the enclosing method's parameter type hints.
     */
    private function resolveCallerTypeFromParams(ClassMethod $method, MethodCall $call): ?string
    {
        if (! isset($call->var) ||
            ! property_exists($call->var, 'name') ||
            $call->var->name === null) {
            return null;
        }

        foreach ($method->params as $param) {
            if (isset($param->var->name) &&
                $param->var->name === $call->var->name &&
                $param->type) {
                return $param->type->toString();
            }
        }

        return null;
    }

    /**
     * Walk the class itself and every ancestor; return true on the first
     * method whose return type names a parent relation (BelongsTo / MorphTo).
     */
    private function modelHasParentInChain(string $className): bool
    {
        if (! $this->reflectionProvider->hasClass($className)) {
            return false;
        }

        $reflection = $this->reflectionProvider->getClass($className);
        $chain = array_merge([$reflection], $reflection->getParents());

        foreach ($chain as $current) {
            if ($this->classDeclaresParentRelation($current)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Inspect a single class (no inheritance) for a method with a parent-
     * relation return type.
     */
    private function classDeclaresParentRelation(ClassReflection $reflection): bool
    {
        $classNode = $this->getASTForClass($reflection->getName());
        if (! $classNode instanceof Class_) {
            return false;
        }

        foreach ($this->getMethodNodes($classNode) as $method) {
            if ($this->isParentRelationReturn($method)) {
                return true;
            }
        }

        return false;
    }

    /**
     * A method declares a parent relationship iff its return type is one of
     * the recognised parent-relation names.
     */
    private function isParentRelationReturn(ClassMethod $classMethod): bool
    {
        if (! $classMethod->returnType instanceof Node) {
            return false;
        }

        return in_array($classMethod->returnType->toString(), self::PARENT_RELATION_TYPES, true);
    }
}
