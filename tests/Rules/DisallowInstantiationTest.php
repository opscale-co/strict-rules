<?php

declare(strict_types=1);

namespace Opscale\Tests\Rules;

use Opscale\Rules\SOLID\DIP\DisallowInstantiationRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(DisallowInstantiationRule::class)]
class DisallowInstantiationTest extends RuleTestCase
{
    private const ERROR_MESSAGE_TEMPLATE = 'Class "%s" violates Dependency Inversion Principle '.
        'by directly instantiating "%s" in method "%s()". '.
        'Consider injecting the dependency through constructor or method parameters.';

    /**
     * Caso positivo — `ExternalAPIService.canBatch()` instancia
     * `BatchingService` directamente. La regla debe reportarlo.
     */
    #[Test]
    public function caso_positivo_instanciacion_de_servicio(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Services/ExternalAPIService.php'],
            [
                [
                    sprintf(
                        self::ERROR_MESSAGE_TEMPLATE,
                        'Opscale\Services\ExternalAPIService',
                        'Opscale\Services\BatchingService',
                        'canBatch'
                    ),
                    24,
                ],
            ]
        );
    }

    /**
     * Caso negativo — `ValidDependencyInjection` recibe sus
     * dependencias por constructor. No debe reportarse.
     */
    #[Test]
    public function caso_negativo_dependency_injection_correcta(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Services/ValidDependencyInjection.php'],
            []
        );
    }

    /**
     * Falso positivo a evitar — `ServiceInstantiatingModel` crea
     * instancias de un Eloquent Model (`new User`, `new Product`) y
     * de un Mailable subclass (`new SendOrderEmail`). Estos son
     * patrones idiomáticos de Laravel y NO deben reportarse. La
     * implementación previa flageaba todo Model/Mailable porque solo
     * tenía una lista cerrada de FQCNs y un set de sufijos
     * heurísticos. La regla actual usa reflexión de subclase.
     */
    #[Test]
    public function falso_positivo_instanciacion_de_modelo_eloquent_y_mailable(): void
    {
        $this->analyse(
            [
                __DIR__.'/../fixtures/Mail/SendOrderEmail.php',
                __DIR__.'/../fixtures/Services/ServiceInstantiatingModel.php',
            ],
            []
        );
    }

    /**
     * Falso negativo a evitar — `MultiInstantiationServices.php`
     * declara dos clases. La primera (`FirstCleanService`) usa DI; la
     * segunda (`SecondViolatingService`) instancia un Service. La
     * implementación previa miraba solo `getRootNode` (la primera) y
     * no reportaba; la regla actual recorre todas las clases del
     * archivo y reporta la segunda.
     */
    #[Test]
    public function falso_negativo_instanciacion_en_segunda_clase_de_archivo_multi_clase(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Services/MultiInstantiationServices.php'],
            [
                [
                    sprintf(
                        self::ERROR_MESSAGE_TEMPLATE,
                        'Opscale\Services\SecondViolatingService',
                        'Opscale\Services\BatchingService',
                        'process'
                    ),
                    19,
                ],
            ]
        );
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new DisallowInstantiationRule($reflectionProvider);
    }
}
