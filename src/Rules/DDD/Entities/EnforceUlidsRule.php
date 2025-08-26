<?php

namespace Opscale\Rules\DDD\Entities;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Opscale\Rules\DDD\DomainRule;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that enforces the use of UseUlids trait in model classes that extend Eloquent Model
 * or subclasses of classes that extend Eloquent Model
 */
class EnforceUlidsRule extends DomainRule
{
    public function __construct(
        ReflectionProvider $reflectionProvider
    ) {
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

        // Check if the class uses the UseUlids trait
        $classNode = $this->getRootNode($node);
        if (! $this->usesTrait($classNode, HasUlids::class)) {
            $error = sprintf(
                'Model class "%s" must use the "HasUlids" trait to ensure ' .
                'consistent ID handling with ULIDs.',
                $classNode->namespacedName->toString()
            );

            $errors[] = RuleErrorBuilder::message($error)
                ->line($classNode->getLine())
                ->identifier('ddd.entities.enforceUlids')
                ->build();
        }

        return $errors;
    }

    /**
     * Check if the class uses the UseUlids trait
     */
    protected function usesTrait(Class_ $class, string $traitName): bool
    {
        $traitUse = $this->getTraitNodes($class);
        if ($traitUse === [] ||
            count($traitUse[0]->traits) === 0) {
            return false;
        }

        foreach ($traitUse[0]->traits as $usedTrait) {
            if ($usedTrait->toString() === $traitName) {
                return true;
            }
        }

        return false;
    }
}
