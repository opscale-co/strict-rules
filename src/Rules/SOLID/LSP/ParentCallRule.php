<?php

namespace Opscale\Rules\SOLID\LSP;

use Opscale\Rules\BaseRule;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that verifies instance methods that override parent methods should call parent::
 * ensuring the extended behavior is compatible with the base class
 * Static methods and methods implementing abstract parent methods are excluded from this rule
 */
class ParentCallRule extends BaseRule
{
    protected function validate(Node $node): array
    {
        assert($node instanceof \PHPStan\Node\FileNode);
        $errors = [];
        $rootNode = $this->getRootNode($node);
        if ($rootNode === null) {
            return [];
        }

        $methods = $this->getMethodNodes($rootNode);
        $classReflection = $this->getClassReflection($node);

        if (! $classReflection instanceof \PHPStan\Reflection\ClassReflection) {
            return $errors;
        }

        foreach ($methods as $method) {
            // Skip static methods - rule only applies to instance methods
            if ($method->isStatic()) {
                continue;
            }

            if (! $this->isOverridingParentMethod($method, $classReflection)) {
                continue;
            }

            if ($this->hasParentCall($method)) {
                continue;
            }

            $error = sprintf(
                'Method "%s::%s()" overrides a parent method but does not call parent::. ' .
                'Methods that override parent behavior should call parent:: to maintain the Liskov Substitution Principle.',
                $rootNode->namespacedName?->toString() ?? 'Unknown',
                $method->name->toString()
            );

            $errors[] = RuleErrorBuilder::message($error)
                ->line($method->getLine())
                ->identifier('solid.lsp.parentCall')
                ->build();
        }

        return $errors;
    }

    protected function shouldProcess(Node $node, Scope $scope): bool
    {
        if (parent::shouldProcess($node, $scope) === false) {
            return false;
        }

        assert($node instanceof \PHPStan\Node\FileNode);
        $parent = $this->getParentNode($node);

        return $parent != null;
    }

    /**
     * Check if the method exists in the parent class with the same signature
     */
    private function isOverridingParentMethod(ClassMethod $classMethod, ClassReflection $classReflection): bool
    {
        $parentClass = $classReflection->getParentClass();
        if (! $parentClass instanceof \PHPStan\Reflection\ClassReflection) {
            return false;
        }

        $methodName = $classMethod->name->toString();

        // Check if the parent class has a method with the same name
        if (! $parentClass->hasNativeMethod($methodName)) {
            return false;
        }

        // Get the parent method to compare signatures
        $extendedMethodReflection = $parentClass->getNativeMethod($methodName);

        // Check if it's not private (private methods can't be overridden)
        if ($extendedMethodReflection->isPrivate()) {
            return false;
        }

        // Skip abstract methods - they don't need parent:: calls
        if ($extendedMethodReflection->isAbstract()) {
            return false;
        }

        // If we got here, the method exists in parent and can be overridden
        return true;
    }

    /**
     * Check if method contains a parent:: call
     */
    private function hasParentCall(ClassMethod $classMethod): bool
    {
        if ($classMethod->stmts === null) {
            return false;
        }

        $nodeFinder = new NodeFinder;
        $parentCalls = $nodeFinder->findInstanceOf($classMethod->stmts, StaticCall::class);

        foreach ($parentCalls as $parentCall) {
            if ($parentCall->class instanceof Node\Name &&
                $parentCall->class->toString() === 'parent') {
                return true;
            }
        }

        return false;
    }
}
