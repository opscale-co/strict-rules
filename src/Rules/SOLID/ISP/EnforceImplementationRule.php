<?php

declare(strict_types=1);

namespace Opscale\Rules\SOLID\ISP;

use Opscale\Rules\BaseRule;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Trait_;
use PHPStan\Node\FileNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that flags stub implementations of interface methods. The three
 * patterns flagged are:
 *
 *  - empty body
 *  - single throw expression
 *  - single return of a default value (null / false / 0 / 0.0 / '' / [])
 *
 * Walks every classlike (Class_, Trait_) declared in the file. Enums
 * are skipped (their interface contract semantics differ).
 *
 * The set of interface method names is resolved via
 * `ClassReflection::getInterfaces()` — transitive — so methods from
 * interfaces inherited through a parent class are also covered.
 */
class EnforceImplementationRule extends BaseRule
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
            $interfaceMethods = $this->collectInterfaceMethods($reflection);
            if ($interfaceMethods === []) {
                continue;
            }

            foreach ($this->getMethodNodes($classNode) as $method) {
                if (! in_array($method->name->toString(), $interfaceMethods, true)) {
                    continue;
                }

                $error = $this->validateMethodImplementation($method, $fqcn);
                if ($error !== null) {
                    $errors[] = $error;
                }
            }
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    private function collectInterfaceMethods(ClassReflection $reflection): array
    {
        $names = [];
        foreach ($reflection->getInterfaces() as $interface) {
            foreach ($interface->getNativeReflection()->getMethods() as $method) {
                if ($method->isPublic()) {
                    $names[] = $method->getName();
                }
            }
        }

        return array_values(array_unique($names));
    }

    private function validateMethodImplementation(ClassMethod $classMethod, string $className): ?IdentifierRuleError
    {
        if ($classMethod->isAbstract()) {
            return null;
        }

        $methodName = $classMethod->name->toString();

        if ($classMethod->stmts === null || $classMethod->stmts === []) {
            return $this->buildError($classMethod, sprintf(
                'Method "%s::%s()" implements an interface but has an empty body. '.
                'Provide a proper implementation instead.',
                $className,
                $methodName
            ));
        }

        if ($this->isShortImplementation($classMethod)) {
            $stmt = $classMethod->stmts[0];

            if ($stmt instanceof Expression && $stmt->expr instanceof Throw_) {
                return $this->buildError($classMethod, sprintf(
                    'Method "%s::%s()" implements an interface but only throws an exception. '.
                    'Provide a proper implementation instead.',
                    $className,
                    $methodName
                ));
            }

            if ($stmt instanceof Return_ && $this->isDefaultValueReturn($stmt)) {
                return $this->buildError($classMethod, sprintf(
                    'Method "%s::%s()" implements an interface but only returns a default value. '.
                    'Provide a proper implementation instead.',
                    $className,
                    $methodName
                ));
            }
        }

        return null;
    }

    private function isShortImplementation(ClassMethod $classMethod): bool
    {
        $stmtCount = count($classMethod->stmts ?? []);
        if ($stmtCount > 2 || $stmtCount === 0) {
            return false;
        }

        $lineCount = $classMethod->getEndLine() - $classMethod->getStartLine() + 1;

        return $lineCount <= 5;
    }

    private function isDefaultValueReturn(Return_ $return): bool
    {
        if (! $return->expr instanceof Node\Expr) {
            return true;
        }

        $expr = $return->expr;

        if ($expr instanceof Scalar) {
            if ($expr instanceof String_ && $expr->value === '') {
                return true;
            }
            if ($expr instanceof LNumber && $expr->value === 0) {
                return true;
            }
            if ($expr instanceof DNumber && $expr->value === 0.0) {
                return true;
            }
        }

        if ($expr instanceof ConstFetch) {
            $constName = strtolower($expr->name->toString());
            if (in_array($constName, ['null', 'false'], true)) {
                return true;
            }
        }

        if ($expr instanceof Array_ && $expr->items === []) {
            return true;
        }

        return false;
    }

    private function buildError(ClassMethod $classMethod, string $message): IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)
            ->line($classMethod->getLine())
            ->identifier('solid.isp.enforceImplementation')
            ->build();
    }

    /**
     * @return list<Class_|Trait_>
     */
    private function getClassLikeNodes(FileNode $fileNode): array
    {
        $nodes = [];
        foreach ($fileNode->getNodes() as $stmt) {
            if (! $stmt instanceof Namespace_) {
                continue;
            }
            foreach ($stmt->stmts as $inner) {
                if ($inner instanceof Class_ || $inner instanceof Trait_) {
                    $nodes[] = $inner;
                } elseif ($inner instanceof Enum_) {
                    // Enums are intentionally skipped — different interface semantics.
                }
            }
        }

        return $nodes;
    }
}
