<?php

namespace Opscale\Rules\DDD\ValueObjects;

use Opscale\Rules\DDD\DomainRule;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that verifies Value Object classes don't contain Eloquent mutators or accessors
 */
class NoAccesorMutatorRule extends DomainRule
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
        $classReflection = $this->getClassReflection($node);
        $methods = $this->getMethodNodes($rootNode);

        foreach ($methods as $method) {
            if ($this->isEloquentMutator($method) ||
                $this->isEloquentAccessor($method) ||
                $this->isAttributeMethod($method)) {
                $error = sprintf(
                    'Model "%s" is defining "%s" and it should not contain Eloquent mutators or accessors. ' .
                    'Custom attribute logic should be defined as a ValueObject.',
                    $classReflection->getName(),
                    $method->name->toString()
                );

                $errors[] = RuleErrorBuilder::message($error)
                    ->line($method->getLine())
                    ->identifier('ddd.valueObjects.noAccesorMutator')
                    ->build();
            }
        }

        return $errors;
    }

    protected function shouldProcess(Node $node, Scope $scope): bool
    {
        // @phpstan-ignore-next-line
        if (! $node instanceof FileNode ||
            parent::shouldProcess($node, $scope) === false ||
            ! $this->isEloquentModel($node)) {
            return false;
        }

        return true;
    }

    /**
     * Check if a method is an Eloquent mutator
     */
    private function isEloquentMutator(ClassMethod $classMethod): bool
    {
        $methodName = $classMethod->name->toString();

        // Check for Laravel 9+ attribute-style mutators (set...Attribute)
        if (preg_match('/^set[A-Z]\w*Attribute$/', $methodName)) {
            return true;
        }

        // Check for older mutator patterns
        if (str_starts_with($methodName, 'set') && str_ends_with($methodName, 'Attribute')) {
            return true;
        }

        return false;
    }

    /**
     * Check if a method is an Eloquent accessor
     */
    private function isEloquentAccessor(ClassMethod $classMethod): bool
    {
        $methodName = $classMethod->name->toString();

        // Check for Laravel 9+ attribute-style accessors (get...Attribute)
        if (preg_match('/^get[A-Z]\w*Attribute$/', $methodName)) {
            return true;
        }

        // Check for older accessor patterns
        if (str_starts_with($methodName, 'get') && str_ends_with($methodName, 'Attribute')) {
            return true;
        }

        return false;
    }

    /**
     * Check if a method uses the new Laravel 9+ Attribute class
     */
    private function isAttributeMethod(ClassMethod $classMethod): bool
    {
        // Check if the method returns an Attribute instance
        if (! $classMethod->returnType instanceof \PhpParser\Node) {
            return false;
        }

        // Check if return type is Attribute
        $returnType = $classMethod->returnType;
        if ($returnType instanceof Name) {
            $returnTypeName = $returnType->toString();
            if ($returnTypeName === 'Attribute' ||
                str_ends_with($returnTypeName, '\\Attribute')) {
                return true;
            }
        }

        // Check method body for Attribute::make calls
        if ($classMethod->stmts) {
            foreach ($classMethod->stmts as $stmt) {
                if ($stmt instanceof Return_ && $stmt->expr instanceof StaticCall) {
                    $staticCall = $stmt->expr;
                    if ($staticCall->class instanceof Name) {
                        $className = $staticCall->class->toString();
                        if ($className === 'Attribute' ||
                            str_ends_with($className, '\\Attribute')) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }
}
