<?php

declare(strict_types=1);

namespace Opscale\Rules\DDD\Aggregates;

use Opscale\Rules\DDD\DomainRule;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that verifies Eloquent Model classes use the "Validatable" trait
 * from the opscale-co/validations package, either directly or through
 * any class in their inheritance chain.
 */
class ModelValidationRule extends DomainRule
{
    /**
     * Fully-qualified name of the only trait that satisfies this rule.
     */
    private const VALIDATABLE_FQCN = 'Opscale\\Validations\\Validatable';

    /**
     * Composer package that ships the trait — referenced in the error
     * message so developers know what to install.
     */
    private const VALIDATABLE_PACKAGE = 'opscale-co/validations';

    protected function validate(Node $node): array
    {
        assert($node instanceof \PHPStan\Node\FileNode);
        $rootNode = $this->getRootNode($node);
        $reflection = $this->getClassReflection($node);

        if ($reflection instanceof ClassReflection &&
            $this->hasValidatableInChain($reflection)) {
            return [];
        }

        $error = sprintf(
            'Model class "%s" must use the "Validatable" trait from the "%s" package '.
            'to declare its validation rules.',
            $rootNode?->namespacedName?->toString() ?? 'Unknown',
            self::VALIDATABLE_PACKAGE
        );

        return [
            RuleErrorBuilder::message($error)
                ->line($rootNode?->getLine() ?? 1)
                ->identifier('ddd.aggregates.modelValidation')
                ->build(),
        ];
    }

    protected function shouldProcess(Node $node, Scope $scope): bool
    {
        if (parent::shouldProcess($node, $scope) === false) {
            return false;
        }

        assert($node instanceof \PHPStan\Node\FileNode);
        if (! $this->isEloquentModel($node)) {
            return false;
        }

        return true;
    }

    /**
     * Walk the class itself and every ancestor; return true on first match.
     */
    private function hasValidatableInChain(ClassReflection $reflection): bool
    {
        $chain = array_merge([$reflection], $reflection->getParents());
        foreach ($chain as $current) {
            if (in_array(self::VALIDATABLE_FQCN, $current->getNativeReflection()->getTraitNames(), true)) {
                return true;
            }
        }

        return false;
    }
}
