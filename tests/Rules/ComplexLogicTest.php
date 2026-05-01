<?php

declare(strict_types=1);

namespace Opscale\Tests\Rules;

use Opscale\Rules\DDD\DomainServices\ComplexLogicRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(ComplexLogicRule::class)]
class ComplexLogicTest extends RuleTestCase
{
    private const ERROR_MESSAGE_TEMPLATE = 'Class "%s" performs operations on %d distinct Eloquent models (%s). '.
        'Complex logic involving more than 2 entities must live in a class under '.
        '"\\Services\\" (typically an Opscale Action under "\\Services\\Actions\\").';

    /**
     * Caso positivo — un Job fuera de \Services\ ejecuta tres operaciones
     * sobre modelos distintos: User::find (StaticCall), $product->save()
     * (MethodCall sobre param tipado), new Tenant() (New_). La regla debe
     * reportar exactamente un error con los tres FQCNs ordenados.
     */
    #[Test]
    public function caso_positivo_tres_modelos_en_operaciones(): void
    {
        $this->analyse(
            [
                __DIR__.'/../fixtures/Models/User.php',
                __DIR__.'/../fixtures/Models/Product.php',
                __DIR__.'/../fixtures/Models/Tenant.php',
                __DIR__.'/../fixtures/Jobs/MultiModelJob.php',
            ],
            [
                [
                    sprintf(
                        self::ERROR_MESSAGE_TEMPLATE,
                        'Opscale\Jobs\MultiModelJob',
                        3,
                        'Opscale\Models\Product, Opscale\Models\Tenant, Opscale\Models\User'
                    ),
                    9,
                ],
            ]
        );
    }

    /**
     * Caso negativo — una clase bajo \Services\ ejecuta operaciones sobre
     * cuatro modelos distintos. Está exenta por scope: \Services\ es el
     * lugar legítimo para coordinación multi-entidad.
     */
    #[Test]
    public function caso_negativo_service_con_muchos_modelos(): void
    {
        $this->analyse(
            [
                __DIR__.'/../fixtures/Models/User.php',
                __DIR__.'/../fixtures/Models/Product.php',
                __DIR__.'/../fixtures/Models/Tenant.php',
                __DIR__.'/../fixtures/Models/OrgPolicy.php',
                __DIR__.'/../fixtures/Services/CrossEntityService.php',
            ],
            []
        );
    }

    /**
     * Falso positivo a evitar — un FormRequest fuera de \Services\
     * referencia tres modelos solo vía type hints y constantes Model::class.
     * Cero operaciones reales. La implementación previa, que contaba
     * imports, lo flageaba. La regla actual NO debe reportarlo.
     */
    #[Test]
    public function falso_positivo_solo_type_hints_y_class_refs(): void
    {
        $this->analyse(
            [
                __DIR__.'/../fixtures/Models/User.php',
                __DIR__.'/../fixtures/Models/Product.php',
                __DIR__.'/../fixtures/Models/Tenant.php',
                __DIR__.'/../fixtures/Http/MultiModelFormRequest.php',
            ],
            []
        );
    }

    /**
     * Falso negativo a evitar — un Job fuera de \Services\ ejecuta tres
     * StaticCall escritos con FQCNs completos y SIN imports. La
     * implementación previa, que contaba `use` statements, dejaba pasar
     * este patrón. La regla actual lo detecta y lo reporta.
     */
    #[Test]
    public function falso_negativo_fqcn_en_linea_sin_imports(): void
    {
        $this->analyse(
            [
                __DIR__.'/../fixtures/Models/User.php',
                __DIR__.'/../fixtures/Models/Product.php',
                __DIR__.'/../fixtures/Models/Tenant.php',
                __DIR__.'/../fixtures/Jobs/FQCNDirectJob.php',
            ],
            [
                [
                    sprintf(
                        self::ERROR_MESSAGE_TEMPLATE,
                        'Opscale\Jobs\FQCNDirectJob',
                        3,
                        'Opscale\Models\Product, Opscale\Models\Tenant, Opscale\Models\User'
                    ),
                    5,
                ],
            ]
        );
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new ComplexLogicRule($reflectionProvider);
    }
}
