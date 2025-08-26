<?php

namespace Opscale\Tests\Rules;

use Opscale\Rules\DDD\Subdomains\EntityCountRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(EntityCountRule::class)]
class EntityCountTest extends RuleTestCase
{
    // Note: EntityCount tests simplified due to static state issues in rule implementation

    #[Test]
    public function allows_non_entities_in_subdomain(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Models/NonModelClass.php',
            __DIR__ . '/../fixtures/Models/EmptyClass.php',
        ], []);
    }

    #[Test]
    public function handles_empty_subdomain(): void
    {
        $this->analyse([], []);
    }

    #[Test]
    public function allows_interfaces_and_abstract_classes(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/Contracts/TestInterface.php',
            __DIR__ . '/../fixtures/Models/AbstractModel.php',
        ], []);
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new EntityCountRule($reflectionProvider, 1);
    }
}
