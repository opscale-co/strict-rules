<?php

namespace Opscale\Rules\Smells;

use Opscale\Rules\BaseRule;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Throw_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that detects dummy catch blocks that don't contain meaningful logic
 */
class EnforceLogicHandlingRule extends BaseRule
{
    protected function shouldProcess(Node $node, Scope $scope): bool
    {
        $namespace = $this->getNamespace($node);
        if (parent::shouldProcess($node, $scope) === false ||
            $this->isInNamespaces($namespace, ['\\Services'])) {
            return false;
        }

        return true;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->shouldProcess($node, $scope)) {
            return [];
        }

        $errors = [];
        $rootNode = $this->getRootNode($node);
        $nodeFinder = new NodeFinder();
        $methods = $this->getMethodNodes($rootNode);

        // Traverse all nodes in the class to find catch statements
        foreach ($methods as $method) {
            $exprs = $nodeFinder->findInstanceOf($method->stmts ?? [], Catch_::class);
            foreach ($exprs as $expr) {
                $error = sprintf(
                    '"%s" class contains try-catch block, exception handling is only allowed in logic. ' .
                    'Consider managing exceptions in Services and manage expected values anywhere else.',
                    $rootNode->namespacedName->toString()
                );

                $errors[] = RuleErrorBuilder::message($error)
                    ->line($expr->getLine())
                    ->build();
            }
        }

        return $errors;
    }
}