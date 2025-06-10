<?php

namespace Opscale\Rules\Smells;

use PhpParser\Node;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\DeclareDeclare;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use Opscale\Rules\BaseRule;

/**
 * Rule that verifies all PHP files have declare(strict_types=1) at the beginning
 */
class StrictTypesRule extends BaseRule
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

        $errors = [];
        $classReflection = $this->getClassReflection($node);
        
        if (!$this->hasStrictTypesDeclaration($node)) {
            $error = sprintf(
                'Class "%s" must have declare(strict_types=1) at the beginning of the file.',
                $classReflection->getName()
            );
            
            $errors[] = RuleErrorBuilder::message($error)
                ->line(1)
                ->build();
        }

        return $errors;
    }

    /**
     * Check if the file has declare(strict_types=1) declaration
     */
    private function hasStrictTypesDeclaration(FileNode $fileNode): bool
    {
        $nodes = $fileNode->getNodes();
        
        // Look for declare statement in the first few nodes (usually at the top)
        foreach ($nodes as $node) {
            if ($node instanceof Declare_) {
                foreach ($node->declares as $declare) {
                    if ($declare instanceof DeclareDeclare &&
                        $declare->key->name === 'strict_types' &&
                        $declare->value->value === 1) {
                        return true;
                    }
                }
            }
            
            // If we encounter a namespace or class before declare, it's missing
            if ($node instanceof Namespace_ ||
                $node instanceof Class_) {
                break;
            }
        }
        
        return false;
    }
}