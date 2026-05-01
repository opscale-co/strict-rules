<?php

declare(strict_types=1);

namespace Opscale\Rules\DDD\Domain;

use Opscale\Rules\DDD\DomainRule;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Do_;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Switch_;
use PhpParser\Node\Stmt\While_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that verifies domain model classes don't contain imperative control
 * flow statements (if, switch, match, for, foreach, while, do-while).
 *
 * Closures and arrow functions are NOT inspected — control flow inside
 * them is encapsulated and is a normal part of Laravel's idiomatic
 * accessor/cast configuration (e.g. `Attribute::make(get: function (...) { ... })`).
 */
class NoStatementsLogicRule extends DomainRule
{
    /**
     * Map of statement key (used in the error message) to AST class.
     *
     * @var array<string, class-string<Node>>
     */
    private const STATEMENT_TYPES = [
        'for' => For_::class,
        'foreach' => Foreach_::class,
        'while' => While_::class,
        'dowhile' => Do_::class,
        'switch' => Switch_::class,
        'match' => Match_::class,
        'if' => If_::class,
    ];

    /**
     * Exposed publicly so the anonymous visitor can read the map without
     * losing scope on `self::`.
     *
     * @return array<string, class-string<Node>>
     */
    public static function statementTypes(): array
    {
        return self::STATEMENT_TYPES;
    }

    protected function validate(Node $node): array
    {
        assert($node instanceof \PHPStan\Node\FileNode);
        if (! $this->isEloquentModel($node)) {
            return [];
        }

        $errors = [];
        $classNode = $this->getRootNode($node);
        if ($classNode === null) {
            return [];
        }

        $methods = $this->getMethodNodes($classNode);

        foreach ($methods as $method) {
            if ($method->name->toString() === '__construct') {
                continue;
            }

            foreach ($this->collectControlFlow($method) as $statement => $occurrences) {
                foreach ($occurrences as $occurrence) {
                    $error = sprintf(
                        'Method "%s::%s" contains a "%s" statement '.
                        'which is not allowed in domain model classes.',
                        $classNode->namespacedName?->toString() ?? 'Unknown',
                        $method->name->toString(),
                        $statement
                    );
                    $errors[] = RuleErrorBuilder::message($error)
                        ->line($occurrence->getLine())
                        ->identifier('ddd.domain.noStatementsLogic')
                        ->build();
                }
            }
        }

        return $errors;
    }

    /**
     * Walk the method body once, collecting every recognised control-flow
     * node, while skipping the bodies of closures and arrow functions.
     *
     * @return array<string, array<Node>>
     */
    private function collectControlFlow(ClassMethod $method): array
    {
        $stmts = $method->stmts ?? [];

        $visitor = new class extends NodeVisitorAbstract
        {
            /** @var array<string, array<Node>> */
            public array $found = [];

            public function enterNode(Node $node): ?int
            {
                if ($node instanceof Closure || $node instanceof ArrowFunction) {
                    return NodeVisitor::DONT_TRAVERSE_CHILDREN;
                }

                foreach (NoStatementsLogicRule::statementTypes() as $key => $class) {
                    if ($node instanceof $class) {
                        $this->found[$key][] = $node;
                        break;
                    }
                }

                return null;
            }
        };

        $traverser = new NodeTraverser;
        $traverser->addVisitor($visitor);
        $traverser->traverse($stmts);

        return $visitor->found;
    }
}
