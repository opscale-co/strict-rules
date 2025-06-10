<?php

namespace Opscale\Rules\DDD\ValueObjects;

use Opscale\Rules\DDD\DomainRule;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * Rule that verifies Value Object classes in Models\ValueObjects implement CastsAttributes interface
 */
class EnforceCastRule extends DomainRule
{
    /**
     * @param ReflectionProvider $reflectionProvider
     */
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        parent::__construct($reflectionProvider);
    }

    protected function shouldProcess(Node $node, Scope $scope): bool
    {
        $namespace = $this->getNamespace($node);
        if (parent::shouldProcess($node, $scope) === false ||
            !$this->isInNamespaces($namespace, ['\\Models\\ValueObjects'])) {
            return false;
        }

        return true;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->shouldProcess($node, $scope)) {
            return [];
        }

        $errors = [];
        $rootNode = $this->getRootNode($node);
        $interfaces = $this->getInterfaceNodes($rootNode);

        // Check if the class implements CastsAttributes interface
        if (!in_array(CastsAttributes::class, $interfaces)) {
            $error = sprintf(
                'ValueObject class "%s" must implement "%s" interface. ' .
                'This interface is required for classes that will be used as Value Objects.',
                $rootNode->namespacedName->toString(),
                CastsAttributes::class
            );

            $errors[] = RuleErrorBuilder::message($error)
                ->line($rootNode->getLine())
                ->build();
        }

        return $errors;
    }
}