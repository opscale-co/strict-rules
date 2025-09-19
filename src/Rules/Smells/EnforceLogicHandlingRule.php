<?php

namespace Opscale\Rules\Smells;

use Opscale\Rules\BaseRule;
use PhpParser\Node;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that detects dummy catch blocks that don't contain meaningful logic
 */
class EnforceLogicHandlingRule extends BaseRule
{
    protected function validate(Node $node): array
    {
        assert($node instanceof \PHPStan\Node\FileNode);
        $errors = [];
        $rootNode = $this->getRootNode($node);
        if ($rootNode === null) {
            return [];
        }

        $nodeFinder = new NodeFinder;

        // Check for exception imports in interaction layers
        $useStatements = $this->getUseStatements($node);
        foreach ($useStatements as $useStatement) {
            $importedClass = $useStatement->name->toString();
            if ($this->isExceptionClass($importedClass)) {
                $error = sprintf(
                    '"%s" class imports exception "%s", exception imports are only allowed in logic layers. ' .
                    'Consider managing exceptions in Services, Models, or Observers only.',
                    $rootNode->namespacedName?->toString() ?? 'Unknown',
                    $importedClass
                );

                $errors[] = RuleErrorBuilder::message($error)
                    ->line($useStatement->getLine())
                    ->identifier('smells.enforceLogicHandling.import')
                    ->build();
            }
        }

        $methods = $this->getMethodNodes($rootNode);

        // Traverse all nodes in the class to find catch statements
        foreach ($methods as $method) {
            $exprs = $nodeFinder->findInstanceOf($method->stmts ?? [], Catch_::class);
            foreach ($exprs as $expr) {
                $error = sprintf(
                    '"%s" class contains try-catch block, exception handling is only allowed in logic. ' .
                    'Consider managing exceptions in Services, Models, or Observers and manage expected values anywhere else.',
                    $rootNode->namespacedName?->toString() ?? 'Unknown'
                );

                $errors[] = RuleErrorBuilder::message($error)
                    ->line($expr->getLine())
                    ->identifier('smells.enforceLogicHandling')
                    ->build();
            }
        }

        return $errors;
    }

    protected function shouldProcess(Node $node, Scope $scope): bool
    {
        if (parent::shouldProcess($node, $scope) === false) {
            return false;
        }

        assert($node instanceof \PHPStan\Node\FileNode);
        $namespace = $this->getNamespace($node);
        if ($this->isInNamespaces($namespace, ['\\Services', '\\Models', '\\Observers'])) {
            return false;
        }

        return true;
    }

    /**
     * Check if the imported class is an exception
     */
    private function isExceptionClass(string $className): bool
    {
        // Check for common exception patterns
        $exceptionPatterns = [
            '/Exception$/',
            '/Error$/',
            '/Throwable$/',
            '/^Exception$/',
            '/^Error$/',
            '/^Throwable$/',
        ];

        foreach ($exceptionPatterns as $exceptionPattern) {
            if (preg_match($exceptionPattern, $className)) {
                return true;
            }
        }

        // Check if it's a known exception class
        $knownExceptions = [
            'Exception',
            'Error',
            'Throwable',
            'RuntimeException',
            'InvalidArgumentException',
            'LogicException',
            'BadMethodCallException',
            'DomainException',
            'OutOfBoundsException',
            'OutOfRangeException',
            'OverflowException',
            'RangeException',
            'UnderflowException',
            'UnexpectedValueException',
            'PDOException',
            'ReflectionException',
        ];

        return in_array($className, $knownExceptions) ||
               in_array(substr($className, strrpos($className, '\\') + 1), $knownExceptions);
    }
}
