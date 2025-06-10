<?php

namespace Opscale\Rules\SOLID\SRP;

use Opscale\Rules\BaseRule;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that verifies class files do not exceed maximum lines limit (500 lines)
 * This enforces Single Responsibility Principle by preventing overly large classes
 */
class MaxLinesRule extends BaseRule
{
    /**
     * Maximum allowed lines per class
     */
    private const MAX_LINES = 500;

    /**
     * @var int
     */
    private int $maxLines;

    /**
     * @param ReflectionProvider $reflectionProvider
     * @param int $maxLines Maximum allowed lines (default: 500)
     */
    public function __construct(
        ReflectionProvider $reflectionProvider,
        int $maxLines = self::MAX_LINES
    ) {
        parent::__construct($reflectionProvider);
        $this->maxLines = $maxLines;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->shouldProcess($node, $scope)) {
            return [];
        }

        $errors = [];

        // Calculate the number of lines in the class
        $rootNode = $this->getRootNode($node);
        $startLine = $node->getStartLine();
        $endLine = $node->getEndLine();
        $totalLines = $endLine - $startLine + 1;

        if ($totalLines > $this->maxLines) {
            $error = sprintf(
                'Class "%s" has %d lines, which exceeds the maximum allowed %d lines. ' .
                'Consider breaking this class into smaller classes to follow the Single Responsibility Principle.',
                $rootNode->namespacedName->toString(),
                $totalLines,
                $this->maxLines
            );

            $errors[] = RuleErrorBuilder::message($error)
                ->line($startLine)
                ->build();
        }

        return $errors;
    }
}