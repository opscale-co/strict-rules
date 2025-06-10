<?php

namespace Opscale\Rules\DDD\Entities;

use Opscale\Rules\DDD\DomainRule;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\RuleErrorBuilder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * Rule that enforces the use of UseUlids trait in model classes that extend Eloquent Model
 * or subclasses of classes that extend Eloquent Model
 */
class EnforceUlidsRule extends DomainRule
{
    /**
     * @param ReflectionProvider $reflectionProvider
     */
    public function __construct(
        ReflectionProvider $reflectionProvider
    ) {
        parent::__construct($reflectionProvider);
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if(!$this->shouldProcess($node, $scope) ||
           !$this->isEloquentModel($node)) {
            return []; // Skip if not a model class
        }

        $errors = [];

        // Check if the class uses the UseUlids trait
        $classNode = $this->getRootNode($node);
        if (!$this->usesTrait($classNode, HasUlids::class)) {
            $error = sprintf(
                'Model class "%s" must use the "HasUlids" trait to ensure ' .
                'consistent ID handling with ULIDs.',
                $classNode->namespacedName->toString()
            );

            $errors[] = RuleErrorBuilder::message($error)
                ->line($classNode->getLine())
                ->build();
        }

        return $errors;
    }

    /**
     * Check if the class uses the UseUlids trait
     *
     * @param ClassReflection $classReflection
     * @return bool
     */
    protected function usesTrait(Node $node, string $trait): bool
    {
        $traitUse = $this->getTraitNodes($node);
        if (count($traitUse) === 0 || 
            count($traitUse[0]->traits) === 0) {
            return false;
        }

        foreach ($traitUse[0]->traits as $trait) {
            if ($trait->name === $trait) {
                return true;
            }
        }

        return false;
    }
}