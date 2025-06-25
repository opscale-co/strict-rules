<?php

namespace Opscale\Rules\DDD\Aggregates;

use Illuminate\Database\Eloquent\Model;
use Opscale\Rules\DDD\DomainRule;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that prevents direct saving of child models that have parent relationships (belongsTo)
 * Child models should only be saved through their parent (Aggregate root)
 */
class ParentChildTransactionRule extends DomainRule
{
    public function processNode(Node $node, Scope $scope): array
    {
        // @phpstan-ignore-next-line
        if (! $node instanceof FileNode ||
            ! $this->shouldProcess($node, $scope)) {
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
                if (! ($call instanceof MethodCall)) {
                    continue;
                }

                if (! ($call->name instanceof Node\Identifier)) {
                    continue;
                }

                if ($call->name->toString() !== 'save') {
                    continue;
                }

                // Get the type of the object from the method parameters
                $callerType = null;
                if (isset($call->var) && (property_exists($call->var, 'name') && $call->var->name !== null)) {
                    foreach ($method->params as $param) {
                        if (isset($param->var->name) && $param->var->name === $call->var->name && $param->type) {
                            $callerType = $param->type->toString();
                            break;
                        }
                    }
                }

                // Check if the caller is an Eloquent model
                // Now check if this model has belongsTo relationships
                if ($this->isEloquentModel($callerType) && $this->modelHasParent($callerType)) {
                    $error = sprintf(
                        'Direct save() on model "%s" is not allowed. ' .
                        'Models with parent relationships (belongsTo) should only be saved through their parent aggregates.',
                        $callerType
                    );
                    $errors[] = RuleErrorBuilder::message($error)
                        ->line($call->getLine())
                        ->identifier('ddd.aggregates.parentChildTransaction')
                        ->build();
                }
            }
        }

        return $errors;
    }

    protected function shouldProcess(Node $node, Scope $scope): bool
    {
        // @phpstan-ignore-next-line
        if (! $node instanceof FileNode ||
            parent::shouldProcess($node, $scope) === false) {
            return false;
        }

        $namespace = $this->getNamespace($node);
        if (! $this->isInNamespaces($namespace, ['\\Models\\Repositories', '\\Services'])) {
            return false;
        }

        return true;
    }

    /**
     * Check if a specific model class has belongsTo relationships by analyzing its source code
     */
    private function modelHasParent(string $className): bool
    {
        $classNode = $this->getASTForClass($className);
        if (! $classNode instanceof \PhpParser\Node\Stmt\Class_) {
            return false;
        }

        $methods = $this->getMethodNodes($classNode);
        foreach ($methods as $method) {
            if ($this->isBelongsToMethod($method)) {
                return true; // Found a belongsTo method
            }
        }

        return false; // No belongsTo methods found
    }

    /**
     * Check if a method returns BelongsTo relationship by analyzing its AST
     */
    private function isBelongsToMethod(ClassMethod $classMethod): bool
    {
        // Check return type annotation
        if ($classMethod->returnType instanceof \PhpParser\Node) {
            $returnTypeName = $classMethod->returnType->toString();
            if ($returnTypeName === 'BelongsTo' ||
                $returnTypeName === \Illuminate\Database\Eloquent\Relations\BelongsTo::class) {
                return true;
            }
        }

        // Check method body for belongsTo() calls
        if ($classMethod->stmts) {
            $nodeFinder = new NodeFinder;
            $methodCalls = $nodeFinder->findInstanceOf($classMethod->stmts, MethodCall::class);
            foreach ($methodCalls as $methodCall) {
                if ($methodCall->name instanceof Node\Identifier &&
                    $methodCall->name->toString() === 'belongsTo') {
                    return true; // Found a belongsTo call
                }
            }
        }

        return false;
    }
}
