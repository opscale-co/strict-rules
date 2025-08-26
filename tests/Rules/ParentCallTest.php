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
                'Method "' . \Opscale\Services\BatchingService::class . '::canBatch()" is annotated with #[\Override] or @overridable but does not call parent::. ' .
                'Methods that override parent behavior should call parent:: to maintain the Liskov Substitution Principle.',
                26,
            ],
        ]);
    }

    #[Test]
    public function allows_classes_without_override_methods(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Models/ValidUlidUser.php',
        ], []);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new ParentCallRule($reflectionProvider);
    }
}
