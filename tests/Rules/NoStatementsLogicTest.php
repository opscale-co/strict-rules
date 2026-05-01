<?php

declare(strict_types=1);

namespace Opscale\Tests\Rules;

use Opscale\Rules\DDD\Domain\NoStatementsLogicRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(NoStatementsLogicRule::class)]
class NoStatementsLogicTest extends RuleTestCase
{
    private const ERROR_MESSAGE_TEMPLATE = 'Method "%s::%s" contains a "%s" statement '.
        'which is not allowed in domain model classes.';

    /**
     * Caso positivo — el método `getEmail` del modelo `User` contiene un
     * `if` directo en su cuerpo, fuera de cualquier closure. La regla debe
     * reportarlo.
     */
    #[Test]
    public function caso_positivo_if_directo(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Models/User.php'],
            [
                [
                    sprintf(self::ERROR_MESSAGE_TEMPLATE, \Opscale\Models\User::class, 'getEmail', 'if'),
                    53,
                ],
            ]
        );
    }

    /**
     * Caso negativo — un modelo con métodos puramente declarativos
     * (`casts()`, `$fillable`, `$hidden`) no debe ser reportado.
     */
    #[Test]
    public function caso_negativo_modelo_declarativo(): void
    {
        $this->analyse([__DIR__.'/../fixtures/Models/ValidUlidUser.php'], []);
    }

    /**
     * Falso positivo a evitar — el `if` vive dentro de un closure pasado
     * a `Attribute::make(get: ...)`. La implementación previa, que usaba
     * `NodeFinder::findInstanceOf` recursivamente, lo flageaba. La regla
     * actual omite el subárbol de closures y NO debe reportarlo.
     */
    #[Test]
    public function falso_positivo_if_dentro_de_closure(): void
    {
        $this->analyse([__DIR__.'/../fixtures/Models/DeclarativeAccessorModel.php'], []);
    }

    /**
     * Falso negativo a evitar — el método `status` retorna un `match`
     * sobre `$this->state`. La implementación previa no contemplaba PHP
     * 8.0 `match` y dejaba pasar este patrón. La regla actual lo detecta
     * y lo reporta como statement-name `match`.
     */
    #[Test]
    public function falso_negativo_match_en_metodo(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Models/MatchUsingModel.php'],
            [
                [
                    sprintf(self::ERROR_MESSAGE_TEMPLATE, \Opscale\Models\MatchUsingModel::class, 'status', 'match'),
                    15,
                ],
            ]
        );
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new NoStatementsLogicRule($reflectionProvider);
    }
}
