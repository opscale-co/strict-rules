<?php

declare(strict_types=1);

namespace Opscale\Rules\SOLID\SRP;

use Opscale\Rules\BaseRule;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;
use PHPStan\Node\FileNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that enforces a class-level line cap to keep Single Responsibility
 * pressure on every class. The cap is measured per `Class_` / `Trait_` /
 * `Enum_` node — not per file — so heavy file headers, license blocks
 * or many `use` imports do not push a small class over the limit.
 *
 * Multi-class files are fully covered: every classlike declaration in
 * the file is measured independently and gets its own error if it
 * exceeds the threshold.
 */
class MaxLinesRule extends BaseRule
{
    private const MAX_LINES = 500;

    private int $maxLines;

    public function __construct(
        ReflectionProvider $reflectionProvider,
        int $maxLines = self::MAX_LINES
    ) {
        parent::__construct($reflectionProvider);
        $this->maxLines = $maxLines;
    }

    protected function validate(Node $node): array
    {
        assert($node instanceof FileNode);
        $errors = [];

        foreach ($this->getClassLikeNodes($node) as $classNode) {
            if (! $classNode->namespacedName instanceof Name) {
                continue;
            }

            $lines = $classNode->getEndLine() - $classNode->getStartLine() + 1;
            if ($lines <= $this->maxLines) {
                continue;
            }

            $error = sprintf(
                'Class "%s" has %d lines, which exceeds the maximum allowed %d lines. '.
                'Consider breaking this class into smaller classes to follow the Single Responsibility Principle.',
                $classNode->namespacedName->toString(),
                $lines,
                $this->maxLines
            );

            $errors[] = RuleErrorBuilder::message($error)
                ->line($classNode->getEndLine())
                ->identifier('solid.srp.maxLines')
                ->build();
        }

        return $errors;
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
