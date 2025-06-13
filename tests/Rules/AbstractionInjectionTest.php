<?php

namespace Opscale\Tests\Rules;

use Opscale\Rules\SOLID\LSP\AbstractionInjectionRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(AbstractionInjectionRule::class)]
class AbstractionInjectionTest extends RuleTestCase
{
    #[Test]
    public function rule(): void
    {
        $this->analyse([
            __DIR__ . '/../app/Services/ExternalAPIService.php',
        ], [
            [
                'Constructor parameter "baseUrl" in class "Opscale\Services\ExternalAPIService" should have a type and be an interface (if it is an object). ' .
                'Follow the Liskov Substitution Principle by depending on abstractions, not concretions.',
                15,
            ],
            [
                'Constructor parameter "request" in class "Opscale\Services\ExternalAPIService" should have a type and be an interface (if it is an object). ' .
                'Follow the Liskov Substitution Principle by depending on abstractions, not concretions.',
                16,
            ],
        ]);
    }

    protected function getRule(): Rule
    {
        $broker = $this->createReflectionProvider();

        return new AbstractionInjectionRule($broker);
    }
}
