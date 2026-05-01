<?php

declare(strict_types=1);

namespace Opscale\Rules\DDD\ValueObjects;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Opscale\Rules\BaseRule;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that flags Eloquent Model methods carrying custom attribute
 * logic (Laravel-style accessors / mutators, or Laravel 9+ Attribute
 * methods). The constitution requires custom attribute logic to live
 * in a Value Object cast (a class implementing CastsAttributes), not
 * inside the Model itself.
 *
 * Detection:
 *   - Mutators:   method name matches `^set[A-Z]\w*Attribute$`.
 *   - Accessors:  method name matches `^get[A-Z]\w*Attribute$`.
 *   - Attribute methods: declared return type OR top-level
 *     `return ::make(...)` resolves to the exact FQCN
 *     `Illuminate\Database\Eloquent\Casts\Attribute`.
 *
 * Walks every Class_ in the file (multi-class supported) and filters
 * per class so non-Eloquent classes are ignored. Eloquent's own
 * framework overrides (`getAttribute`, `setAttribute`) do not match
 * the regex (no capital letter immediately after `set`/`get`) and are
 * therefore not flagged.
 */
class NoAccesorMutatorRule extends BaseRule
{
    private const MODELS_NAMESPACE = '\\Models';

    private const MUTATOR_REGEX = '/^set[A-Z]\w*Attribute$/';

    private const ACCESSOR_REGEX = '/^get[A-Z]\w*Attribute$/';

    private const ATTRIBUTE_FQCN = Attribute::class;

    private const ERROR_TEMPLATE = 'Model "%s" is defining "%s" and it should not contain Eloquent '.
        'mutators or accessors. Custom attribute logic should be defined as a ValueObject.';

    protected function shouldProcess(Node $node, Scope $scope): bool
    {
        if (! $node instanceof FileNode) {
            return false;
        }

        $namespace = $this->getNamespace($node);

        return $this->isInNamespaces($namespace, [self::MODELS_NAMESPACE]);
    }

    protected function validate(Node $node): array
    {
        assert($node instanceof FileNode);
        $errors = [];

        foreach ($this->getClassNodes($node) as $classNode) {
            if (! $classNode->namespacedName instanceof Name) {
                continue;
            }

            $fqcn = $classNode->namespacedName->toString();
            if (! $this->isEloquentModelClassName($fqcn)) {
                continue;
            }

            foreach ($this->getMethodNodes($classNode) as $method) {
                if ($this->isMutator($method) ||
                    $this->isAccessor($method) ||
                    $this->isAttributeMethod($method)) {
                    $errors[] = $this->buildError($fqcn, $method);
                }
            }
        }

        return $errors;
    }

    private function isMutator(ClassMethod $classMethod): bool
    {
        return preg_match(self::MUTATOR_REGEX, $classMethod->name->toString()) === 1;
    }

    private function isAccessor(ClassMethod $classMethod): bool
    {
        return preg_match(self::ACCESSOR_REGEX, $classMethod->name->toString()) === 1;
    }

    private function isAttributeMethod(ClassMethod $classMethod): bool
    {
        if ($classMethod->returnType instanceof Name &&
            $classMethod->returnType->toString() === self::ATTRIBUTE_FQCN) {
            return true;
        }

        if ($classMethod->stmts === null) {
            return false;
        }

        foreach ($classMethod->stmts as $stmt) {
            if (! $stmt instanceof Return_) {
                continue;
            }
            if (! $stmt->expr instanceof StaticCall) {
                continue;
            }
            if (! $stmt->expr->class instanceof Name) {
                continue;
            }
            if ($stmt->expr->class->toString() === self::ATTRIBUTE_FQCN) {
                return true;
            }
        }

        return false;
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

    private function buildError(string $fqcn, ClassMethod $classMethod): \PHPStan\Rules\IdentifierRuleError
    {
        return RuleErrorBuilder::message(sprintf(
            self::ERROR_TEMPLATE,
            $fqcn,
            $classMethod->name->toString()
        ))
            ->line($classMethod->getLine())
            ->identifier('ddd.valueObjects.noAccesorMutator')
            ->build();
    }
}
