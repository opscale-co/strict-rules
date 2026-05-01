<?php

declare(strict_types=1);

namespace Opscale\Tests\Rules;

use Opscale\Rules\DDD\Domain\Helpers\EntityCountCollector;
use Opscale\Rules\DDD\Subdomains\EntityCountRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(EntityCountRule::class)]
#[CoversClass(EntityCountCollector::class)]
class EntityCountTest extends RuleTestCase
{
    private const MAX_CLASSES = 2;

    private const ERROR_MESSAGE_TEMPLATE = 'Subdomain "%s" has %d entities, which exceeds the maximum of %d entities. '.
        'Consider splitting this subdomain into smaller, more focused subdomains.';

    /**
     * Caso positivo — tres modelos Eloquent concretos comparten el
     * subdomain `Opscale\Models`. Con `maxClasses = 2`, la regla debe
     * emitir un error indicando 3 entidades sobre el límite de 2.
     */
    #[Test]
    public function caso_positivo_subdomain_excede_limite(): void
    {
        $this->analyse(
            [
                __DIR__.'/../fixtures/Models/Tenant.php',
                __DIR__.'/../fixtures/Models/Product.php',
                __DIR__.'/../fixtures/Models/ValidatedModel.php',
            ],
            [
                [sprintf(self::ERROR_MESSAGE_TEMPLATE, 'Opscale\Models', 3, self::MAX_CLASSES), 1],
            ]
        );
    }

    /**
     * Caso negativo — dos modelos Eloquent concretos en el subdomain
     * `Opscale\Models`. Igual al límite de 2, pero la regla usa `>`
     * (estricto), por lo que NO debe reportar.
     */
    #[Test]
    public function caso_negativo_subdomain_dentro_del_limite(): void
    {
        $this->analyse(
            [
                __DIR__.'/../fixtures/Models/Tenant.php',
                __DIR__.'/../fixtures/Models/Product.php',
            ],
            []
        );
    }

    /**
     * Falso positivo a evitar — clases no-Eloquent (helper plano,
     * clase vacía, modelo abstracto, interface) NO cuentan hacia el
     * límite. Aunque haya muchas, no deben gatillar la regla.
     */
    #[Test]
    public function falso_positivo_clases_no_eloquent_no_cuentan(): void
    {
        $this->analyse(
            [
                __DIR__.'/../fixtures/Models/NonModelClass.php',
                __DIR__.'/../fixtures/Models/EmptyClass.php',
                __DIR__.'/../fixtures/Models/AbstractModel.php',
                __DIR__.'/../fixtures/Contracts/TestInterface.php',
            ],
            []
        );
    }

    /**
     * Falso negativo a evitar — dos modelos en `Opscale\Models` y un
     * modelo en `Opscale\Modules\Foo\Models` suman 3 entidades, pero
     * pertenecen a subdominios distintos. La regla debe agrupar por
     * subdominio: cada uno está dentro del límite, NO se reporta.
     * Una implementación que sumara todo sin agrupar habría flageado.
     */
    #[Test]
    public function falso_negativo_subdominios_no_se_lumpean(): void
    {
        $this->analyse(
            [
                __DIR__.'/../fixtures/Models/Tenant.php',
                __DIR__.'/../fixtures/Models/Product.php',
                __DIR__.'/../fixtures/Modules/Foo/Models/FooEntity.php',
            ],
            []
        );
    }

    protected function getRule(): Rule
    {
        return new EntityCountRule(self::MAX_CLASSES);
    }

    protected function getCollectors(): array
    {
        return [new EntityCountCollector($this->createReflectionProvider())];
    }
}
