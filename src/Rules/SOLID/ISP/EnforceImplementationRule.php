<?php

namespace Opscale\Rules\SOLID\ISP;

use Opscale\Rules\BaseRule;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Scalar;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use Throwable;

/**
 * Rule that ensures classes properly implement interface methods
 * without throwing generic exceptions or returning default values
 */
class EnforceImplementationRule extends BaseRule
{
    public function processNode(Node $node, Scope $scope): array
    {
        // @phpstan-ignore-next-line
        if (! $node instanceof FileNode ||
            ! $this->shouldProcess($node, $scope)) {
            return [];
        }

        $rootNode = $this->getRootNode($node);

        // Skip enums as they don't have the same interface implementation requirements
        if ($rootNode instanceof Enum_) {
            return [];
        }

        $implementedInterfaces = $this->getInterfaceNodes($rootNode);
        if ($implementedInterfaces === []) {
            return []; // Skip classes that don't implement interfaces
        }

        $errors = [];
        $classReflection = $this->getClassReflection($node);

        // Get all interface methods that need to be implemented
        $interfaceMethods = $this->getInterfaceMethods($implementedInterfaces);

        foreach ($this->getMethodNodes($rootNode) as $method) {
            $methodName = $method->name->toString();

            // Check if this method implements an interface method
            if (! in_array($methodName, $interfaceMethods)) {
                continue;
            }

            // Check for improper implementations
            $error = $this->validateMethodImplementation(
                $method,
                $classReflection->getName());
            if ($error instanceof \PHPStan\Rules\RuleError) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    /**
     * Get all method names from implemented interfaces
     */
    private function getInterfaceMethods(array $interfaces): array
    {
        $methods = [];

        foreach ($interfaces as $interface) {
            try {
                if ($this->reflectionProvider->hasClass($interface)) {
                    $interfaceReflection = $this->reflectionProvider->getClass($interface);
                    $interfaceReflection = $interfaceReflection->getNativeReflection();

                    foreach ($interfaceReflection->getMethods() as $method) {
                        if ($method->isPublic()) {
                            $methods[] = $method->getName();
                        }
                    }
                }
            } catch (Throwable $e) {
                // Skip if we can't reflect the interface
                continue;
            }
        }

        return array_unique($methods);
    }

    /**
     * Validate that a method properly implements interface contract
     */
    private function validateMethodImplementation(ClassMethod $classMethod, string $className): ?RuleError
    {
        $methodName = $classMethod->name->toString();

        // Skip abstract methods
        if ($classMethod->isAbstract()) {
            return null;
        }

        // Check if method body is empty
        if ($classMethod->stmts === null || $classMethod->stmts === []) {
            $error = sprintf(
                'Method "%s::%s()" implements an interface but has an empty body. ' .
                'Provide a proper implementation instead.',
                $className,
                $methodName
            );

            return RuleErrorBuilder::message($error)
                ->line($classMethod->getLine())
                ->identifier('solid.isp.enforceImplementation')
                ->build();
        }

        // Check if it's a short implementation (few statements and lines)
        if ($this->isShortImplementation($classMethod)) {
            $stmt = $classMethod->stmts[0];
            // Single throw statement
            if ($stmt instanceof Expression && $stmt->expr instanceof Throw_) {
                $error = sprintf(
                    'Method "%s::%s()" implements an interface but only throws an exception. ' .
                    'Provide a proper implementation instead.',
                    $className,
                    $methodName
                );

                return RuleErrorBuilder::message($error)
                    ->line($classMethod->getLine())
                    ->identifier('solid.isp.enforceImplementation')
                    ->build();
            }

            // Single throw statement
            if ($stmt instanceof Return_ && $this->isDefaultValueReturn($stmt)) {
                $error = sprintf(
                    'Method "%s::%s()" implements an interface but only returns a default value. ' .
                    'Provide a proper implementation instead.',
                    $className,
                    $methodName
                );

                return RuleErrorBuilder::message($error)
                    ->line($classMethod->getLine())
                    ->identifier('solid.isp.enforceImplementation')
                    ->build();
            }
        }

        return null;
    }

    /**
     * Check if a method has a short implementation (likely a placeholder)
     */
    private function isShortImplementation(ClassMethod $classMethod): bool
    {
        // Check statement count (1-2 statements only)
        $stmtCount = count($classMethod->stmts);
        if ($stmtCount > 2) {
            return false;
        }

        // Check line count (method should span few lines)
        $startLine = $classMethod->getStartLine();
        $endLine = $classMethod->getEndLine();
        $lineCount = $endLine - $startLine + 1;

        return $stmtCount <= 2 && $lineCount <= 5;
    }

    /**
     * Check if a return statement returns a default/empty value
     */
    private function isDefaultValueReturn(Return_ $return): bool
    {
        if (! $return->expr instanceof \PhpParser\Node\Expr) {
            return true; // Empty return
        }

        $expr = $return->expr;

        // Check for scalar default values
        if ($expr instanceof Scalar) {
            // Empty string, zero, false, null
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

        // Check for explicit null, false, empty array
        if ($expr instanceof ConstFetch) {
            $constName = $expr->name->toString();
            if (in_array(strtolower($constName), ['null', 'false'])) {
                return true;
            }
        }

        // Check for empty array
        if ($expr instanceof Array_ && $expr->items === []) {
            return true;
        }

        return false;
    }
}
