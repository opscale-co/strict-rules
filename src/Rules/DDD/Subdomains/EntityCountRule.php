<?php

namespace Opscale\Rules\DDD\Subdomains;

use Opscale\Rules\DDD\DomainRule;
use PhpParser\Node;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that limits the number of classes in a subdomain
 */
class EntityCountRule extends DomainRule
{
    private static int $processedModels = 0;

    /**
     * Default maximum number of classes in a subdomain
     */
    private const DEFAULT_MAX_CLASSES = 10;

    private int $maxClasses;

    public function __construct(
        ReflectionProvider $reflectionProvider,
        int $maxClasses = self::DEFAULT_MAX_CLASSES
    ) {
        parent::__construct($reflectionProvider);
        $this->maxClasses = $maxClasses;
    }

    protected function validate(Node $node): array
    {
        assert($node instanceof \PHPStan\Node\FileNode);
        if (! $this->isEloquentModel($node)) {
            return []; // Skip if not a model class
        }

        // Check if the count exceeds the limit
        $errors = [];
        if (self::$processedModels > $this->maxClasses) {
            $error = sprintf(
                'Subdomain has %d entities, which exceeds the maximum of %d entities. ' .
                'Consider splitting this subdomain into smaller, more focused subdomains.',
                self::$processedModels,
                $this->maxClasses
            );

            $namespaceNode = $this->getNamespaceNode($node);
            $errors[] = RuleErrorBuilder::message($error)
                ->line($namespaceNode instanceof \PhpParser\Node\Stmt\Namespace_ ? $namespaceNode->getLine() : 1)
                ->identifier('ddd.subdomains.entityCount')
                ->build();
        }

        return $errors;
    }
}
