<?php

declare(strict_types=1);

namespace Opscale\Rules\SOLID\OCP;

use Opscale\Rules\BaseRule;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;
use PHPStan\Node\FileNode;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that enforces public/protected methods are explicitly closed
 * (`final`) or explicitly opened (`#[\Override]`). PHP defaults to
 * virtual dispatch, so any non-final, non-Override method is an
 * accidental extension point.
 *
 * Skips:
 *  - private methods (no polymorphism)
 *  - abstract methods (cannot be final by language design)
 *  - magic methods (`__`-prefixed) — they have engine-level semantics
 *    and conventionally are not marked final to preserve subclass
 *    chaining via `parent::__construct()` and friends.
 *
 * Walks every classlike (`Class_`, `Trait_`, `Enum_`) declared in the
 * file so multi-class layouts are fully covered.
 */
class ConditionalOverrideRule extends BaseRule
{
    protected function validate(Node $node): array
    {
        assert($node instanceof FileNode);
        $errors = [];

        foreach ($this->getClassLikeNodes($node) as $classNode) {
            if (! $classNode->namespacedName instanceof Name) {
                continue;
            }

            foreach ($this->getMethodNodes($classNode) as $method) {
                if (! $this->shouldFlag($method)) {
                    continue;
                }

                $error = sprintf(
                    'Method "%s::%s()" must be final unless annotated with #[\Override]. '.
                    'Public and protected methods should be explicitly marked as final to follow the Open/Closed Principle.',
                    $classNode->namespacedName->toString(),
                    $method->name->toString()
                );

                $errors[] = RuleErrorBuilder::message($error)
                    ->line($method->getLine())
                    ->identifier('solid.ocp.conditionalOverride')
                    ->build();
            }
        }

        return $errors;
    }

    private function shouldFlag(ClassMethod $method): bool
    {
        if (! $this->isPublicOrProtected($method)) {
            return false;
        }
        if ($method->isAbstract()) {
            return false;
        }
        if (str_starts_with($method->name->toString(), '__')) {
            return false;
        }
        if ($method->isFinal()) {
            return false;
        }
        if ($this->hasOverrideAttribute($method)) {
            return false;
        }

        return true;
    }

    private function isPublicOrProtected(ClassMethod $classMethod): bool
    {
        return $classMethod->isPublic() || $classMethod->isProtected();
    }

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
