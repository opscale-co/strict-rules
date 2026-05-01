<?php

declare(strict_types=1);

namespace Opscale\Tests\Rules;

use Opscale\Rules\SOLID\ISP\EnforceImplementationRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(EnforceImplementationRule::class)]
class EnforceImplementationTest extends RuleTestCase
{
    private const EMPTY_BODY_TEMPLATE = 'Method "%s::%s()" implements an interface but has an empty body. '.
        'Provide a proper implementation instead.';

    private const THROW_ONLY_TEMPLATE = 'Method "%s::%s()" implements an interface but only throws an exception. '.
        'Provide a proper implementation instead.';

    private const DEFAULT_RETURN_TEMPLATE = 'Method "%s::%s()" implements an interface but only returns a default value. '.
        'Provide a proper implementation instead.';

    /**
     * Caso positivo — `BatchingService` declara `implements Batchable` y
     * sus tres métodos son stubs (default-return, throw-only, empty).
     * La regla debe emitir un error por cada uno.
     */
    #[Test]
    public function caso_positivo_implementaciones_stub(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Services/BatchingService.php'],
            [
                [sprintf(self::DEFAULT_RETURN_TEMPLATE, 'Opscale\Services\BatchingService', 'processBatch'), 12],
                [sprintf(self::THROW_ONLY_TEMPLATE, 'Opscale\Services\BatchingService', 'getBatchStatus'), 17],
                [sprintf(self::EMPTY_BODY_TEMPLATE, 'Opscale\Services\BatchingService', 'completeBatch'), 22],
            ]
        );
    }

    /**
     * Caso negativo — `ValidAddress` implementa `CastsAttributes` con
     * cuerpos sustantivos multi-statement. No debe reportarse.
     */
    #[Test]
    public function caso_negativo_implementacion_completa(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Models/ValueObjects/ValidAddress.php'],
            []
        );
    }

    /**
     * Falso positivo a evitar — `IntentionalShortMethodService` tiene
     * implementaciones cortas (1 statement, ≤ 5 líneas) pero NO son
     * stubs: cada método llama un helper real o usa parámetros. La
     * regla solo flagea throw-only / default-return / empty body, así
     * que estos métodos NO deben reportarse.
     */
    #[Test]
    public function falso_positivo_metodo_corto_no_stub(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Services/IntentionalShortMethodService.php'],
            []
        );
    }

    /**
     * Falso negativo a evitar — `StubChildOverrider extends ConcreteParentImplementer`
     * (que `implements InheritedContract`). El hijo no declara
     * `implements InheritedContract` directamente, pero hereda el
     * contrato vía la clase padre y sobrescribe el método con un
     * single-throw stub. La implementación previa, que solo miraba el
     * `implements` directo del AST, no detectaba esto. La regla actual
     * usa `ClassReflection::getInterfaces()` (transitivo) y reporta.
     */
    #[Test]
    public function falso_negativo_stub_de_interface_heredada_via_clase_padre(): void
    {
        $this->analyse(
            [
                __DIR__.'/../fixtures/Contracts/InheritedContract.php',
                __DIR__.'/../fixtures/Models/ConcreteParentImplementer.php',
                __DIR__.'/../fixtures/Models/StubChildOverrider.php',
            ],
            [
                [
                    sprintf(self::THROW_ONLY_TEMPLATE, 'Opscale\Models\StubChildOverrider', 'inheritedMethod'),
                    9,
                ],
            ]
        );
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new EnforceImplementationRule($reflectionProvider);
    }
}
