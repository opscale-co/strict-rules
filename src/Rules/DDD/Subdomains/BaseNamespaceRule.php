<?php

declare(strict_types=1);

namespace Opscale\Rules\DDD\Subdomains;

use Illuminate\Database\Eloquent\Model;
use Opscale\Rules\BaseRule;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that enforces Eloquent domain entities live directly under a
 * `\Models` namespace segment. Subfolders under `\Models` (aggregate-
 * grouping subnamespaces such as `\Models\Order\OrderItem`) are not
 * allowed — the rule expects a flat layout.
 *
 * The rule walks every Class_ declaration in the file (not just the
 * first), so multi-class files no longer let an Eloquent class slip
 * through behind a non-Eloquent helper.
 */
class BaseNamespaceRule extends BaseRule
{
    private const ERROR_TEMPLATE = 'Class "%s" extends Eloquent Model but is not located '.
        'directly under a "\\Models" namespace. Move the class so its file-level namespace '.
        'ends with "\\Models" (no subfolders allowed under Models).';

    protected function shouldProcess(Node $node, Scope $scope): bool
    {
        return $node instanceof FileNode;
    }

    protected function validate(Node $node): array
    {
        assert($node instanceof FileNode);
        $namespace = $this->getNamespace($node);
        if (str_ends_with($namespace, '\\Models')) {
            return [];
        }

        $errors = [];
        foreach ($this->getClassNodes($node) as $classNode) {
            if (! $classNode->namespacedName instanceof Name) {
                continue;
            }

            $fqcn = $classNode->namespacedName->toString();
            if (! $this->isEloquentModelClassName($fqcn)) {
                continue;
            }

            $errors[] = $this->buildError($fqcn, $classNode);
        }

        return $errors;
    }

    private function buildError(string $fqcn, Class_ $classNode): \PHPStan\Rules\IdentifierRuleError
    {
        return RuleErrorBuilder::message(sprintf(self::ERROR_TEMPLATE, $fqcn))
            ->line($classNode->getLine())
            ->identifier('ddd.subdomains.baseNamespace')
            ->build();
    }

    private function isEloquentModelClassName(string $fqcn): bool
    {
        if (! $this->reflectionProvider->hasClass($fqcn)) {
            return false;
        }

        $reflection = $this->reflectionProvider->getClass($fqcn);
        if ($reflection->isAnonymous() ||
            $reflection->isInterface() ||
            $reflection->isTrait() ||
            $reflection->isEnum()) {
            return false;
        }

        if ($reflection->getName() === Model::class) {
            return true;
        }

        return $reflection->isSubclassOf(Model::class);
    }
}
