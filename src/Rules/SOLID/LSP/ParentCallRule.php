<?php

namespace Opscale\Rules\SOLID\LSP;

use Opscale\Rules\BaseRule;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that verifies methods annotated with #[\Override] or @overridable should call parent::
 * ensuring the extended behavior is compatible with the base class
 */
class ParentCallRule extends BaseRule
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
        $methods = $this->getMethodNodes($rootNode);
        $this->getClassReflection($node);

        foreach ($methods as $method) {
            if (! $this->hasOverrideAttribute($method) &&
                ! $this->hasOverridableAnnotation($method)) {
                continue;
            }

            if ($this->hasParentCall($method)) {
                continue;
            }

            $error = sprintf(
                'Method "%s::%s()" is annotated with #[\\Override] or @overridable but does not call parent::. ' .
                'Methods that override parent behavior should call parent:: to maintain the Liskov Substitution Principle.',
                $rootNode->namespacedName->toString(),
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
        // @phpstan-ignore-next-line
        if (! $node instanceof FileNode ||
            parent::shouldProcess($node, $scope) === false) {
            return false;
        }

        $parent = $this->getParentNode($node);

        return $parent != null;
    }

    /**
     * Check if method has #[\Override] attribute
     */
    private function hasOverrideAttribute(ClassMethod $classMethod): bool
    {
        foreach ($classMethod->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if ($attr->name->toString() === 'Override') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if method has @overridable annotation in docblock
     */
    private function hasOverridableAnnotation(ClassMethod $classMethod): bool
    {
        $docComment = $classMethod->getDocComment();
        if (! $docComment instanceof \PhpParser\Comment\Doc) {
            return false;
        }

        $docText = $docComment->getText();

        return strpos($docText, '@overridable') !== false;
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
