<?php

namespace Opscale\Rules\DDD\Domain;

use Opscale\Rules\DDD\DomainRule;
use PhpParser\Node;
use PhpParser\Node\Stmt\Do_;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Switch_;
use PhpParser\Node\Stmt\While_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that verifies domain model classes don't contain loop constructs or complex control flow
 */
class NoStatementsLogicRule extends DomainRule
{
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        parent::__construct($reflectionProvider);
    }

    public function processNode(Node $node, Scope $scope): array
    {
        // @phpstan-ignore-next-line
        if (! $node instanceof FileNode ||
            ! $this->shouldProcess($node, $scope) ||
            ! $this->isEloquentModel($node)) {
            return []; // Skip if not a model class
        }

        $errors = [];
        $classNode = $this->getRootNode($node);
        $nodeFinder = new NodeFinder;
        $methods = $this->getMethodNodes($classNode);

        foreach ($methods as $method) {
            // Skip constructor methods
            if ($method->name->toString() === '__construct') {
                continue;
            }

            // Find all loop constructs and control flow statements within the method
            $statements = [];
            $statements['for'] = $nodeFinder->findInstanceOf($method->stmts ?? [], For_::class);
            $statements['foreach'] = $nodeFinder->findInstanceOf($method->stmts ?? [], Foreach_::class);
            $statements['while'] = $nodeFinder->findInstanceOf($method->stmts ?? [], While_::class);
            $statements['dowhile'] = $nodeFinder->findInstanceOf($method->stmts ?? [], Do_::class);
            $statements['switch'] = $nodeFinder->findInstanceOf($method->stmts ?? [], Switch_::class);
            $statements['if'] = $nodeFinder->findInstanceOf($method->stmts ?? [], If_::class);

            // Check for for loops
            foreach ($statements as $statement => $ocurrences) {
                foreach ($ocurrences as $ocurrence) {
                    $error = sprintf(
                        'Method "%s::%s" contains a "%s" statement ' .
                        'which is not allowed in domain model classes.',
                        $classNode->namespacedName->toString(),
                        $method->name->toString(),
                        $statement
                    );

                    $errors[] = RuleErrorBuilder::message($error)
                        ->line($ocurrence->getLine())
                        ->identifier('ddd.domain.noStatementsLogic')
                        ->build();
                }
            }
        }

        return $errors;
    }
}
