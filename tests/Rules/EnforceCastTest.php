<?php

declare(strict_types=1);

namespace Opscale\Tests\Rules;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Opscale\Rules\DDD\ValueObjects\EnforceCastRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(EnforceCastRule::class)]
class EnforceCastTest extends RuleTestCase
{
    private const ERROR_MESSAGE_TEMPLATE = 'ValueObject class "%s" must implement "%s" interface '.
        '(directly, via a parent class, or via interface inheritance). The cast contract is '.
        'required for every concrete class under "\\Models\\ValueObjects".';

    /**
     * Caso positivo — la clase Address bajo \Models\ValueObjects no
     * implementa CastsAttributes ni directa ni transitivamente. Debe
     * ser reportada.
     */
    #[Test]
    public function caso_positivo_value_object_sin_casts_attributes(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Models/ValueObjects/Address.php'],
            [
                [
                    sprintf(self::ERROR_MESSAGE_TEMPLATE, 'Opscale\Models\ValueObjects\Address', CastsAttributes::class),
                    9,
                ],
            ]
        );
    }

    /**
     * Caso negativo — dos formas válidas: (a) implementar CastsAttributes
     * directamente (ValidAddress) y (b) heredarlo de un padre abstracto
     * (InheritingValueObject extends AbstractCastableValueObject).
     * Ninguna debe ser reportada.
     */
    #[Test]
    public function caso_negativo_value_object_directo_y_heredado(): void
    {
        $this->analyse(
            [
                __DIR__.'/../fixtures/Models/ValueObjects/ValidAddress.php',
                __DIR__.'/../fixtures/Models/ValueObjects/AbstractCastableValueObject.php',
                __DIR__.'/../fixtures/Models/ValueObjects/InheritingValueObject.php',
            ],
            []
        );
    }

    /**
     * Falso positivo a evitar — InheritingValueObject hereda
     * CastsAttributes de su padre abstracto pero no lo redeclara en su
     * propio implements. La implementación previa, que solo miraba el
     * implements clause literal, lo flageaba. La regla actual usa
     * ClassReflection::getInterfaces (transitivo) y NO debe reportar.
     */
    #[Test]
    public function falso_positivo_value_object_con_interface_heredada(): void
    {
        $this->analyse(
            [
                __DIR__.'/../fixtures/Models/ValueObjects/AbstractCastableValueObject.php',
                __DIR__.'/../fixtures/Models/ValueObjects/InheritingValueObject.php',
            ],
            []
        );
    }

    /**
     * Falso negativo a evitar — un archivo declara dos VOs: el primero
     * (FirstVO) implementa CastsAttributes correctamente, el segundo
     * (SecondVO) no. La implementación previa miraba solo la primera
     * clase y dejaba pasar al segundo. La regla actual recorre todas
     * las clases del archivo y reporta SecondVO.
     */
    #[Test]
    public function falso_negativo_segunda_clase_sin_interface_en_archivo_multi_clase(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Models/ValueObjects/MultiVOFile.php'],
            [
                [
                    sprintf(self::ERROR_MESSAGE_TEMPLATE, 'Opscale\Models\ValueObjects\SecondVO', CastsAttributes::class),
                    21,
                ],
            ]
        );
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new EnforceCastRule($reflectionProvider);
    }
}
