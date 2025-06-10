<?php

namespace Opscale\Rules\DDD\Subdomains;

use Opscale\Rules\DDD\DomainRule;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that limits the number of classes in a subdomain
 */
class EntityCountRule extends DomainRule
{
    /**
     * Default maximum number of classes in a subdomain
     */
    private const DEFAULT_MAX_CLASSES = 10;
    
    /**
     * @var int
     */
    private int $maxClasses;

    private static int $processedModels = 0;

    /**
     * @param ReflectionProvider $reflectionProvider
     * @param int $maxClasses
     */
    public function __construct(
        ReflectionProvider $reflectionProvider,
        int $maxClasses = self::DEFAULT_MAX_CLASSES
    ) {
        parent::__construct($reflectionProvider);
        $this->maxClasses = $maxClasses;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->isEloquentModel($node)) {
            return [];
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

            $errors[] = RuleErrorBuilder::message($error)
                ->line($this->getNamespaceNode($node)->getLine())
                ->build();
        }
        
        return $errors;
    }
}