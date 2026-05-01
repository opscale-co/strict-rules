<?php

declare(strict_types=1);

namespace Opscale\Rules\Smells;

use Opscale\Rules\BaseRule;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeFinder;
use PHPStan\Node\FileNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that detects dummy catch blocks that don't contain meaningful logic.
 *
 * Dummy patterns flagged:
 *  - empty body
 *  - single `return` statement
 *  - single `throw` of an existing variable / method-call result
 *
 * Wrapping throws are NOT flagged: `throw new SomeClass(...)` inside a
 * single-statement catch is the canonical PHP idiom for adding context
 * while preserving the original cause via the `$previous` argument.
 *
 * Walks every classlike (Class_, Trait_, Enum_) declared in the file.
 */
class NoDummyCatchesRule extends BaseRule
{
    protected function validate(Node $node): array
    {
        assert($node instanceof FileNode);
        $errors = [];
        $nodeFinder = new NodeFinder;

        foreach ($this->getClassLikeNodes($node) as $classNode) {
            foreach ($this->getMethodNodes($classNode) as $method) {
                /** @var list<Catch_> $catches */
                $catches = $nodeFinder->findInstanceOf($method->stmts ?? [], Catch_::class);
                foreach ($catches as $catch) {
                    $error = $this->validateCatchBlock($catch);
                    if ($error !== null) {
                        $errors[] = $error;
                    }
                }
            }
        }

        return $errors;
    }

    private function validateCatchBlock(Catch_ $catch): ?IdentifierRuleError
    {
        $stmts = $catch->stmts;
        $exceptionTypes = [];
        foreach ($catch->types as $type) {
            $exceptionTypes[] = $type->toString();
        }
        $exceptions = implode('|', $exceptionTypes);

        if ($stmts === []) {
            return $this->buildError($catch, sprintf(
                'Empty catch block for exception type(s) "%s". '.
                'Either handle the exception properly or remove the try-catch block.',
                $exceptions
            ));
        }

        if (count($stmts) === 1 && $stmts[0] instanceof Return_) {
            return $this->buildError($catch, sprintf(
                'Catch block for exception type(s) "%s" only contains a return statement. '.
                'Consider if the exception should be logged or handled before returning.',
                $exceptions
            ));
        }

        if (count($stmts) === 1 &&
            $stmts[0] instanceof Expression &&
            $stmts[0]->expr instanceof Throw_) {

            // Wrapping the original exception in a new exception type is a
            // legitimate idiom (`throw new BusinessException('...', 0, $e);`).
            // Only flag bare rethrows or non-instantiation throws.
            if ($stmts[0]->expr->expr instanceof New_) {
                return null;
            }

            return $this->buildError($catch, sprintf(
                'Catch block for exception type(s) "%s" only contains a throw statement. '.
                'Consider if the exception should be logged or handled before throwing.',
                $exceptions
            ));
        }

        return null;
    }

    private function buildError(Catch_ $catch, string $message): IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)
            ->line($catch->getLine())
            ->identifier('smells.noDummyCatches')
            ->build();
    }

    /**
     * @return list<Class_|Trait_|Enum_>
     */
    private function getClassLikeNodes(FileNode $fileNode): array
    {
        $nodes = [];
        foreach ($fileNode->getNodes() as $stmt) {
            if (! $stmt instanceof Namespace_) {
                continue;
            }
            foreach ($stmt->stmts as $inner) {
                if ($inner instanceof Class_ ||
                    $inner instanceof Trait_ ||
                    $inner instanceof Enum_) {
                    $nodes[] = $inner;
                }
            }
        }

        return $nodes;
    }
}
