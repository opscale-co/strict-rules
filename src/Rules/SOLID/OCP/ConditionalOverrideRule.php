<?php

namespace Opscale\Rules\SOLID\OCP;

use Opscale\Rules\BaseRule;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that ensures all public and protected methods are final
 * unless annotated with #[\Override]
 * Abstract methods are excluded as they cannot be final
 */
class ConditionalOverrideRule extends BaseRule
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
        $this->getClassReflection($node);

        foreach ($methods as $method) {
            if (! $this->isPublicOrProtected($method)) {
                continue;
            }

            // Skip abstract methods - they cannot be final
            if ($method->isAbstract()) {
                continue;
            }

            if ($method->isFinal()) {
                continue;
            }

            if ($this->hasOverrideAttribute($method)) {
                continue;
            }

            $error = sprintf(
                'Method "%s::%s()" must be final unless annotated with #[\Override]. ' .
                'Public and protected methods should be explicitly marked as final to follow the Open/Closed Principle.',
                $rootNode->namespacedName?->toString() ?? 'Unknown',
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
}
