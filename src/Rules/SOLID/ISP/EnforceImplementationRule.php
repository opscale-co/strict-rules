<?php

namespace Opscale\Rules\SOLID\ISP;

use Opscale\Rules\BaseRule;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Reflection\ReflectionProvider;

/**
 * Rule that ensures classes properly implement interface methods
 * without throwing generic exceptions or returning default values
 */
class EnforceImplementationRule extends BaseRule
{
    /**
     * @param ReflectionProvider $reflectionProvider
     */
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        parent::__construct($reflectionProvider);
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->shouldProcess($node, $scope)) {
            return [];
        }

        $rootNode = $this->getRootNode($node);
        $implementedInterfaces = $this->getInterfaceNodes($rootNode);
        if (empty($implementedInterfaces)) {
            return []; // Skip classes that don't implement interfaces
        }

        $errors = [];
        $classReflection = $this->getClassReflection($node);
        
        // Get all interface methods that need to be implemented
        $interfaceMethods = $this->getInterfaceMethods($implementedInterfaces);
        
        foreach ($this->getMethodNodes($rootNode) as $method) {
            $methodName = $method->name->toString();
            
            // Check if this method implements an interface method
            if (!in_array($methodName, $interfaceMethods)) {
                continue;
            }

            // Check for improper implementations
            $error = $this->validateMethodImplementation(
                $method, 
                $classReflection->getName());
            if ($error !== null) {
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
        
        foreach ($interfaces as $interfaceName) {
            try {
                if ($this->reflectionProvider->hasClass($interfaceName)) {
                    $interfaceReflection = $this->reflectionProvider->getClass($interfaceName);
                    $interfaceReflection = $interfaceReflection->getNativeReflection();
                    
                    foreach ($interfaceReflection->getMethods() as $method) {
                        if ($method->isPublic()) {
                            $methods[] = $method->getName();
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Skip if we can't reflect the interface
                continue;
            }
        }
        
        return array_unique($methods);
    }

    /**
     * Validate that a method properly implements interface contract
     */
    private function validateMethodImplementation(ClassMethod $method, string $className): ?RuleError
    {
        $methodName = $method->name->toString();

        // Skip abstract methods
        if ($method->isAbstract()) {
            return $errors;
        }

        // Check if method body is empty
        if (empty($method->stmts)) {
            return RuleErrorBuilder::message(
                sprintf(
                    'Method "%s::%s()" implements an interface but has an empty body. ' .
                    'Provide a proper implementation instead.',
                    $className,
                    $methodName
                )
            )->line($method->getLine())->build();
        }

        // Check if it's a short implementation (few statements and lines)
        if ($this->isShortImplementation($method)) {
            $stmt = $method->stmts[0];
            
            // Single throw statement
            if ($stmt->expr instanceof Throw_) {
                return RuleErrorBuilder::message(
                    sprintf(
                        'Method "%s::%s()" implements an interface but only throws an exception. ' .
                        'Provide a proper implementation instead.',
                        $className,
                        $methodName
                    )
                )->line($method->getLine())->build();
            }
            // Single return with default value
            elseif ($stmt instanceof Return_ && $this->isDefaultValueReturn($stmt)) {
                return RuleErrorBuilder::message(
                    sprintf(
                        'Method "%s::%s()" implements an interface but only returns a default value. ' .
                        'Provide a proper implementation instead.',
                        $className,
                        $methodName
                    )
                )->line($method->getLine())->build();
            }
        }

        return null;
    }

    /**
     * Check if a method has a short implementation (likely a placeholder)
     */
    private function isShortImplementation(ClassMethod $method): bool
    {
        // Check statement count (1-2 statements only)
        $stmtCount = count($method->stmts);
        if ($stmtCount > 2) {
            return false;
        }
        
        // Check line count (method should span few lines)
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $lineCount = $endLine - $startLine + 1;
        
        return $stmtCount <= 2 && $lineCount <= 5;
    }

    /**
     * Check if a return statement returns a default/empty value
     */
    private function isDefaultValueReturn(Return_ $return): bool
    {
        if ($return->expr === null) {
            return true; // Empty return
        }

        $expr = $return->expr;

        // Check for scalar default values
        if ($expr instanceof Scalar) {
            // Empty string, zero, false, null
            if ($expr instanceof Scalar\String_ && $expr->value === '') {
                return true;
            }
            if ($expr instanceof Scalar\LNumber && $expr->value === 0) {
                return true;
            }
            if ($expr instanceof Scalar\DNumber && $expr->value === 0.0) {
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
        if ($expr instanceof Array_ && empty($expr->items)) {
            return true;
        }

        return false;
    }
}