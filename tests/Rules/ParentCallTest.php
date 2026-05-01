<?php

declare(strict_types=1);

namespace Opscale\Tests\Rules;

use Opscale\Rules\SOLID\LSP\ParentCallRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(ParentCallRule::class)]
class ParentCallTest extends RuleTestCase
{
    private const ERROR_MESSAGE_TEMPLATE = 'Method "%s::%s()" overrides a parent method but does not call parent::. '.
        'Methods that override parent behavior should call parent:: to maintain the Liskov Substitution Principle.';

    /**
     * Caso positivo — `BatchingService.canBatch()` sobrescribe un método
     * concreto del padre `ExternalAPIService` sin llamar `parent::`. La
     * regla debe reportarlo.
     */
    #[Test]
    public function caso_positivo_override_sin_parent_call(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Services/BatchingService.php'],
            [
                [
                    sprintf(self::ERROR_MESSAGE_TEMPLATE, 'Opscale\Services\BatchingService', 'canBatch'),
                    26,
                ],
            ]
        );
    }

    /**
     * Caso negativo — `ProperOverrider` extiende `AbstractParentModel`
     * y todos sus overrides de métodos concretos invocan `parent::`. El
     * único método sin parent es `getName`, que implementa un método
     * abstracto y por tanto está exento.
     */
    #[Test]
    public function caso_negativo_override_con_parent_call(): void
    {
        $this->analyse(
            [
                __DIR__.'/../fixtures/Models/AbstractParentModel.php',
                __DIR__.'/../fixtures/Models/ProperOverrider.php',
            ],
            []
        );
    }

    /**
     * Falso positivo a evitar — `AbstractImplementer` extiende
     * `AbstractParentModel` e implementa `getName()` (método abstracto
     * en el padre). No hay cuerpo padre que invocar; la regla debe
     * exentar a las implementaciones de métodos abstractos.
     */
    #[Test]
    public function falso_positivo_implementacion_de_metodo_abstracto(): void
    {
        $this->analyse(
            [
                __DIR__.'/../fixtures/Models/AbstractParentModel.php',
                __DIR__.'/../fixtures/Models/AbstractImplementer.php',
            ],
            []
        );
    }

    /**
     * Falso negativo a evitar — `MultiClassParentCall.php` declara dos
     * clases. La primera (`FirstProperOverrider`) llama `parent::`. La
     * segunda (`SecondImproperOverrider`) sobrescribe sin `parent::`.
     * La implementación previa miraba solo `getRootNode` (la primera) y
     * no reportaba nada; la regla actual recorre ambas y reporta la
     * segunda.
     */
    #[Test]
    public function falso_negativo_segunda_clase_con_override_sin_parent_en_archivo_multi_clase(): void
    {
        $this->analyse(
            [
                __DIR__.'/../fixtures/Models/AbstractParentModel.php',
                __DIR__.'/../fixtures/Models/MultiClassParentCall.php',
            ],
            [
                [
                    sprintf(self::ERROR_MESSAGE_TEMPLATE, 'Opscale\Models\SecondImproperOverrider', 'getDescription'),
                    25,
                ],
            ]
        );
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new ParentCallRule($reflectionProvider);
    }
}
