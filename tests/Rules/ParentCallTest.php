<?php

namespace Opscale\Tests\Rules;

use Opscale\Rules\SOLID\LSP\ParentCallRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(ParentCallRule::class)]
class ParentCallTest extends RuleTestCase
{
    #[Test]
    public function detects_override_methods_without_parent_call(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Services/BatchingService.php',
        ], [
            [
                'Method "' . \Opscale\Services\BatchingService::class . '::canBatch()" overrides a parent method but does not call parent::. ' .
                'Methods that override parent behavior should call parent:: to maintain the Liskov Substitution Principle.',
                26,
            ],
        ]);
    }

    #[Test]
    public function detects_methods_without_parent_calls(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Models/ValidUlidUser.php',
        ], [
            [
                'Method "' . \Opscale\Models\ValidUlidUser::class . '::casts()" overrides a parent method but does not call parent::. ' .
                'Methods that override parent behavior should call parent:: to maintain the Liskov Substitution Principle.',
                25,
            ],
        ]);
    }

    #[Test]
    public function skips_static_methods(): void
    {
        $this->analyse([__DIR__ . '/../fixtures/Models/StaticMethodsModel.php'], [
            // Only the instance method without parent:: call should trigger
            [
                'Method "' . \Opscale\Models\StaticMethodsModel::class . '::save()" overrides a parent method but does not call parent::. ' .
                'Methods that override parent behavior should call parent:: to maintain the Liskov Substitution Principle.',
                17,
            ],
        ]);
    }

    #[Test]
    public function skips_abstract_method_implementations(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Models/AbstractParentModel.php',
            __DIR__ . '/../fixtures/Models/ConcreteChildModel.php',
        ], [
            // Only the concrete method override without parent:: call should trigger
            [
                'Method "' . \Opscale\Models\ConcreteChildModel::class . '::getDescription()" overrides a parent method but does not call parent::. ' .
                'Methods that override parent behavior should call parent:: to maintain the Liskov Substitution Principle.',
                14,
            ],
        ]);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new ParentCallRule($reflectionProvider);
    }
}
