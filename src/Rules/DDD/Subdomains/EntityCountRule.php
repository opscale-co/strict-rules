<?php

namespace Opscale\Rules\DDD\Subdomains;

use Opscale\Rules\DDD\DomainRule;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
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

    public function processNode(Node $node, Scope $scope): array
    {
        // @phpstan-ignore-next-line
        if (! ($node instanceof FileNode) ||
            ! $this->isEloquentModel($node)) {
            return []; // Skip if not a model class
        }

        // Check if the class is in the root\Models namespace
        $pattern = '/^(\w+\\\\){1,2}(' . self::MODELS_NAMESPACE . ')/';
        $namespace = $this->getNamespace($node);
        if (preg_match($pattern, $namespace)) {
            self::$processedModels++;
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
                ->line($namespaceNode ? $namespaceNode->getLine() : 1)
                ->identifier('ddd.subdomains.entityCount')
                ->build();
        }

        return $errors;
    }
}
