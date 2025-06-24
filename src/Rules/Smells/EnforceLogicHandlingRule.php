<?php

namespace Opscale\Rules\Smells;

use Opscale\Rules\BaseRule;
use PhpParser\Node;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that detects dummy catch blocks that don't contain meaningful logic
 */
class EnforceLogicHandlingRule extends BaseRule
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
        $nodeFinder = new NodeFinder;
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
                    ->identifier('smells.enforceLogicHandling')
                    ->build();
            }
        }

        return $errors;
    }

    protected function shouldProcess(Node $node, Scope $scope): bool
    {
        // @phpstan-ignore-next-line
        if (! $node instanceof FileNode ||
            parent::shouldProcess($node, $scope) === false) {
            return false;
        }

        $namespace = $this->getNamespace($node);
        if ($this->isInNamespaces($namespace, ['\\Services'])) {
            return false;
        }

        return true;
    }
}
