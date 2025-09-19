<?php

namespace Opscale\Rules\DDD\ValueObjects;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Opscale\Rules\DDD\DomainRule;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Rule that verifies Value Object classes in Models\ValueObjects implement CastsAttributes interface
 */
class EnforceCastRule extends DomainRule
{
    protected function validate(Node $node): array
    {
        assert($node instanceof \PHPStan\Node\FileNode);
        $errors = [];
        $rootNode = $this->getRootNode($node);
        if ($rootNode === null) {
            return [];
        }

        $interfaces = $this->getInterfaceNodes($rootNode);

        // Check if the class implements CastsAttributes interface
        if (! in_array(CastsAttributes::class, $interfaces)) {
            $error = sprintf(
                'ValueObject class "%s" must implement "%s" interface. ' .
                'This interface is required for classes that will be used as Value Objects.',
                $rootNode->namespacedName?->toString() ?? 'Unknown',
                CastsAttributes::class
            );

            $errors[] = RuleErrorBuilder::message($error)
                ->line($rootNode->getLine())
                ->identifier('ddd.valueObjects.enforceCast')
                ->build();
        }

        return $errors;
    }

    protected function shouldProcess(Node $node, Scope $scope): bool
    {
        if (parent::shouldProcess($node, $scope) === false) {
            return false;
        }

        assert($node instanceof \PHPStan\Node\FileNode);
        $namespace = $this->getNamespace($node);
        if (! $this->isInNamespaces($namespace, ['\\Models\\ValueObjects'])) {
            return false;
        }

        return true;
    }
}
