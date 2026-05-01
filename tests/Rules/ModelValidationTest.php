<?php

declare(strict_types=1);

namespace Opscale\Tests\Rules;

use Opscale\Rules\DDD\Aggregates\ModelValidationRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(ModelValidationRule::class)]
class ModelValidationTest extends RuleTestCase
{
    /**
     * Caso positivo — modelo Eloquent en \Models sin el trait Validatable
     * debe ser reportado como violación.
     */
    #[Test]
    public function caso_positivo_modelo_sin_trait(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Models/Product.php'],
            [
                [
                    'Model class "Opscale\Models\Product" must use the "Validatable" trait '.
                    'from the "opscale-co/validations" package to declare its validation rules.',
                    10,
                ],
            ]
        );
    }

    /**
     * Caso negativo — modelo Eloquent en \Models que usa el trait oficial
     * Opscale\Validations\Validatable no debe ser reportado.
     */
    #[Test]
    public function caso_negativo_modelo_con_trait(): void
    {
        $this->analyse([__DIR__.'/../fixtures/Models/ValidatedModel.php'], []);
    }

    /**
     * Falso positivo a evitar — un hijo cuyo padre abstracto ya usa el
     * trait Validatable es compliant por herencia y NO debe ser reportado.
     * La implementación previa (que solo miraba el cuerpo de la clase)
     * habría flageado a ValidatedChild.
     */
    #[Test]
    public function falso_positivo_hijo_de_padre_validado(): void
    {
        $this->analyse(
            [
                __DIR__.'/../fixtures/Models/ValidatedAggregateRoot.php',
                __DIR__.'/../fixtures/Models/ValidatedChild.php',
            ],
            []
        );
    }

    /**
     * Falso negativo a evitar — un trait con el mismo nombre corto
     * "Validatable" pero proveniente de otro namespace no satisface la
     * regla. La implementación previa (con str_ends_with sobre el sufijo)
     * habría dejado pasar a PretendingValidatedModel.
     */
    #[Test]
    public function falso_negativo_trait_homonimo(): void
    {
        $this->analyse(
            [
                __DIR__.'/../fixtures/Models/Pretenders/Validatable.php',
                __DIR__.'/../fixtures/Models/PretendingValidatedModel.php',
            ],
            [
                [
                    'Model class "Opscale\Models\PretendingValidatedModel" must use the "Validatable" trait '.
                    'from the "opscale-co/validations" package to declare its validation rules.',
                    8,
                ],
            ]
        );
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new ModelValidationRule($reflectionProvider);
    }
}
