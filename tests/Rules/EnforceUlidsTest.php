<?php

declare(strict_types=1);

namespace Opscale\Tests\Rules;

use Opscale\Rules\DDD\Entities\EnforceUlidsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(EnforceUlidsRule::class)]
class EnforceUlidsTest extends RuleTestCase
{
    private const MISSING_TRAIT_MESSAGE = 'Model class "%s" must use the "HasUlids" trait to ensure '.
        'consistent ID handling with ULIDs.';

    private const DISABLED_TRAIT_MESSAGE = 'Model class "%s" uses the "HasUlids" trait but explicitly '.
        'disables it via property overrides ($incrementing or $keyType). Remove these overrides so '.
        'ULID identity remains consistent.';

    /**
     * Caso positivo — un modelo Eloquent en \Models que no usa HasUlids
     * directamente ni lo hereda de un ancestro debe ser reportado.
     */
    #[Test]
    public function caso_positivo_modelo_sin_trait(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Models/User.php'],
            [
                [
                    sprintf(self::MISSING_TRAIT_MESSAGE, 'Opscale\Models\User'),
                    13,
                ],
            ]
        );
    }

    /**
     * Caso negativo — la regla NO debe reportar ni cuando el modelo declara
     * HasUlids directamente (ValidUlidUser) ni cuando lo hereda de un
     * padre abstracto (InheritingUlidEntity extends AbstractUlidEntity).
     */
    #[Test]
    public function caso_negativo_modelo_con_trait_directo_e_heredado(): void
    {
        $this->analyse(
            [
                __DIR__.'/../fixtures/Models/ValidUlidUser.php',
                __DIR__.'/../fixtures/Models/AbstractUlidEntity.php',
                __DIR__.'/../fixtures/Models/InheritingUlidEntity.php',
            ],
            []
        );
    }

    /**
     * Falso positivo a evitar — `HasUlids` aparece en una segunda
     * declaración `use ...;`. La implementación previa solo inspeccionaba
     * `$traitUse[0]` y reportaba erróneamente. La regla actual recorre
     * todas las declaraciones y NO debe reportar.
     */
    #[Test]
    public function falso_positivo_trait_en_segunda_declaracion(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Models/MultiStmtUlidModel.php'],
            []
        );
    }

    /**
     * Falso negativo a evitar — el modelo declara `use HasUlids;` pero
     * neutraliza el trait con `public $incrementing = true;` y
     * `protected $keyType = 'int';`. La implementación previa lo aprobaba
     * por la presencia del trait. La regla actual detecta la
     * neutralización y reporta con un mensaje específico.
     */
    #[Test]
    public function falso_negativo_trait_neutralizado_por_property_override(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Models/DisabledUlidModel.php'],
            [
                [
                    sprintf(self::DISABLED_TRAIT_MESSAGE, 'Opscale\Models\DisabledUlidModel'),
                    8,
                ],
            ]
        );
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new EnforceUlidsRule($reflectionProvider);
    }
}
