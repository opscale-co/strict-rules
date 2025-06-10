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
class NoDummyCatchesRule extends BaseRule
{
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
                if ($expr instanceof Catch_) {
                    $error = $this->validateCatchBlock($expr, $scope);
                    if ($error) {
                        $errors[] = $error;
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Validate if a catch block is dummy or meaningless
     */
    private function validateCatchBlock(Catch_ $catchNode, Scope $scope): ?RuleError
    {
        $stmts = $catchNode->stmts;
        $exceptionTypes = [];
        
        foreach ($catchNode->types as $type) {
            $exceptionTypes[] = $type->toString();
        }
        
        $exceptions = implode('|', $exceptionTypes);

        // Check if catch block is completely empty
        if (empty($stmts)) {
            return RuleErrorBuilder::message(
                sprintf(
                    'Empty catch block for exception type(s) "%s". ' .
                    'Either handle the exception properly or remove the try-catch block.',
                    $exceptions
                )
            )->line($catchNode->getLine())->build();
        }

        // Check if catch block only contains a return statement
        if (count($stmts) === 1 && $stmts[0] instanceof Return_) {
            $returnStmt = $stmts[0];
            return RuleErrorBuilder::message(
                sprintf(
                    'Catch block for exception type(s) "%s" only contains a return statement. ' .
                    'Consider if the exception should be logged or handled before returning.',
                    $exceptions
                )
            )->line($catchNode->getLine())->build();
        }

        // Check if catch block only contains a throw statement
        if (count($stmts) === 1 && $stmts[0] instanceof Throw_) {
            $returnStmt = $stmts[0];
            return RuleErrorBuilder::message(
                sprintf(
                    'Catch block for exception type(s) "%s" only contains a throw statement. ' .
                    'Consider if the exception should be logged or handled before returning.',
                    $exceptions
                )
            )->line($catchNode->getLine())->build();
        }

        return null;
    }
}