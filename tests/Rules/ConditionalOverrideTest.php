<?php

declare(strict_types=1);

namespace Opscale\Tests\Rules;

use Opscale\Rules\SOLID\OCP\ConditionalOverrideRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(ConditionalOverrideRule::class)]
class ConditionalOverrideTest extends RuleTestCase
{
    private const ERROR_MESSAGE_TEMPLATE = 'Method "%s::%s()" must be final unless annotated with #[\Override]. '.
        'Public and protected methods should be explicitly marked as final to follow the Open/Closed Principle.';

    /**
     * Caso positivo — el modelo `Product` define `isInStock()` como
     * método público sin `final`, sin `#[\Override]` y no abstracto.
     * La regla debe reportarlo.
     */
    #[Test]
    public function caso_positivo_metodo_sin_final_ni_override(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Models/Product.php'],
            [
                [
                    sprintf(self::ERROR_MESSAGE_TEMPLATE, 'Opscale\Models\Product', 'isInStock'),
                    17,
                ],
            ]
        );
    }

    /**
     * Caso negativo — el modelo `SimpleModel` no declara métodos
     * públicos ni protegidos, solo properties y `use` de un trait.
     * No debe reportarse.
     */
    #[Test]
    public function caso_negativo_clase_sin_metodos_publicos_violadores(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Models/SimpleModel.php'],
            []
        );
    }

    /**
     * Falso positivo a evitar — `MagicMethodsModel` define `__construct`,
     * `__toString` y `__invoke` (todos magic methods de PHP, sin `final`).
     * La implementación previa los flageaba; la regla actual los excluye
     * por convención (`__`-prefijo).
     */
    #[Test]
    public function falso_positivo_magic_methods(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Models/MagicMethodsModel.php'],
            []
        );
    }

    /**
     * Falso negativo a evitar — `MultiClassConditionalOverride.php`
     * declara dos clases. La primera (`FirstFinalClass`) tiene su único
     * método marcado `final`. La segunda (`SecondNonFinalClass`) tiene
     * un método público sin `final`. La implementación previa miraba
     * solo `getRootNode` (la primera clase) y no reportaba nada; la
     * regla actual recorre todas las clases y reporta la segunda.
     */
    #[Test]
    public function falso_negativo_segunda_clase_con_metodo_sin_final_en_archivo_multi_clase(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Models/MultiClassConditionalOverride.php'],
            [
                [
                    sprintf(self::ERROR_MESSAGE_TEMPLATE, 'Opscale\Models\SecondNonFinalClass', 'getName'),
                    17,
                ],
            ]
        );
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new ConditionalOverrideRule($reflectionProvider);
    }
}
