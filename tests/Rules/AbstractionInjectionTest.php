<?php

namespace Opscale\Tests\Rules;

use PHPStan\Rules\Rule;
use Opscale\Rules\SOLID\LSP\AbstractionInjectionRule;
use PHPStan\Testing\RuleTestCase;

class AbstractionInjectionTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $broker = $this->createReflectionProvider();
        return new AbstractionInjectionRule($broker);
    }

    public function testRule(): void
    {
        $this->analyse([
                __DIR__ . '/../app/Services/ExternalAPIService.php'
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
                ]
         ]);
    }
}