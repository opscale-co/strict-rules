<?php

namespace Opscale\Rules\SOLID\OCP;

use Opscale\Rules\BaseRule;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that ensures all public and protected methods are final
 * unless annotated with #[\Override] or @overridable
 */
class ConditionalOverrideRule extends BaseRule
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
            if (! $this->isPublicOrProtected($method)) {
                continue;
            }

            if ($method->isFinal()) {
                continue;
            }

            if ($this->hasOverrideAttribute($method)) {
                continue;
            }

            if ($this->hasOverridableAnnotation($method)) {
                continue;
            }

            $error = sprintf(
                'Method "%s::%s()" must be final unless annotated with #[\Override] or @overridable. ' .
                'Public and protected methods should be explicitly marked as final to follow the Open/Closed Principle.',
                $rootNode->namespacedName->toString(),
                $method->name->toString()
            );

            $errors[] = RuleErrorBuilder::message($error)
                ->line($method->getLine())
                ->identifier('solid.ocp.conditionalOverride')
                ->build();
        }

        return $errors;
    }

    /**
     * Check if method is public or protected
     */
    private function isPublicOrProtected(ClassMethod $classMethod): bool
    {
        if ($classMethod->isPublic()) {
            return true;
        }

        return $classMethod->isProtected();
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
}
