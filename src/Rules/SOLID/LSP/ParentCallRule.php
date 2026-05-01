<?php

declare(strict_types=1);

namespace Opscale\Rules\SOLID\LSP;

use Opscale\Rules\BaseRule;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeFinder;
use PHPStan\Node\FileNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that flags instance methods which override a concrete parent
 * method without calling `parent::`. The Liskov Substitution Principle
 * requires subclass behaviour to remain compatible with the base
 * class; calling `parent::` preserves the contract unless the override
 * is intentionally a complete replacement.
 *
 * Skipped:
 *  - static methods (LSP applies to instance polymorphism)
 *  - methods that implement abstract parent methods (no body to call)
 *  - methods that override private parent methods (private isn't inherited)
 *  - methods with a `parent::*` call anywhere in their body
 *
 * Walks every classlike (Class_, Trait_, Enum_) in the file so
 * multi-class layouts are fully covered.
 */
class ParentCallRule extends BaseRule
{
    protected function validate(Node $node): array
    {
        assert($node instanceof FileNode);
        $errors = [];

        foreach ($this->getClassLikeNodes($node) as $classNode) {
            if (! $classNode->namespacedName instanceof Name) {
                continue;
            }

            $fqcn = $classNode->namespacedName->toString();
            if (! $this->reflectionProvider->hasClass($fqcn)) {
                continue;
            }

            $reflection = $this->reflectionProvider->getClass($fqcn);
            if ($reflection->getParentClass() === null) {
                continue;
            }

            foreach ($this->getMethodNodes($classNode) as $method) {
                if ($method->isStatic()) {
                    continue;
                }

                if (! $this->isOverridingParentMethod($method, $reflection)) {
                    continue;
                }

                if ($this->hasParentCall($method)) {
                    continue;
                }

                $errors[] = RuleErrorBuilder::message(sprintf(
                    'Method "%s::%s()" overrides a parent method but does not call parent::. '.
                    'Methods that override parent behavior should call parent:: to maintain the Liskov Substitution Principle.',
                    $fqcn,
                    $method->name->toString()
                ))
                    ->line($method->getLine())
                    ->identifier('solid.lsp.parentCall')
                    ->build();
            }
        }

        return $errors;
    }

    private function isOverridingParentMethod(ClassMethod $classMethod, ClassReflection $classReflection): bool
    {
        $parentClass = $classReflection->getParentClass();
        if ($parentClass === null) {
            return false;
        }

        $methodName = $classMethod->name->toString();
        if (! $parentClass->hasNativeMethod($methodName)) {
            return false;
        }

        $parentMethod = $parentClass->getNativeMethod($methodName);
        if ($parentMethod->isPrivate()) {
            return false;
        }

        return ! $parentMethod->isAbstract();
    }

    private function hasParentCall(ClassMethod $classMethod): bool
    {
        if ($classMethod->stmts === null) {
            return false;
        }

        $nodeFinder = new NodeFinder;
        $staticCalls = $nodeFinder->findInstanceOf($classMethod->stmts, StaticCall::class);

        foreach ($staticCalls as $staticCall) {
            if ($staticCall->class instanceof Name && $staticCall->class->toString() === 'parent') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<Class_|Trait_|Enum_>
     */
    private function getClassLikeNodes(FileNode $fileNode): array
    {
        $nodes = [];
        foreach ($fileNode->getNodes() as $stmt) {
            if (! $stmt instanceof Namespace_) {
                continue;
            }
            foreach ($stmt->stmts as $inner) {
                if ($inner instanceof Class_ ||
                    $inner instanceof Trait_ ||
                    $inner instanceof Enum_) {
                    $nodes[] = $inner;
                }
            }
        }

        return $nodes;
    }
}
