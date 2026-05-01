<?php

declare(strict_types=1);

namespace Opscale\Tests\Rules;

use Opscale\Rules\SOLID\SRP\MaxLinesRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(MaxLinesRule::class)]
class MaxLinesTest extends RuleTestCase
{
    private const MAX_LINES = 25;

    private const ERROR_MESSAGE_TEMPLATE = 'Class "%s" has %d lines, which exceeds the maximum allowed %d lines. '.
        'Consider breaking this class into smaller classes to follow the Single Responsibility Principle.';

    /**
     * Caso positivo — el modelo `User` tiene un cuerpo de clase de 48
     * líneas (entre `class User extends Authenticatable` y la última `}`).
     * Con el threshold 25, excede y debe ser reportado.
     */
    #[Test]
    public function caso_positivo_clase_excede_limite(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Models/User.php'],
            [
                [
                    sprintf(self::ERROR_MESSAGE_TEMPLATE, 'Opscale\Models\User', 49, self::MAX_LINES),
                    61,
                ],
            ]
        );
    }

    /**
     * Caso negativo — el modelo `ValidSmallUser` tiene un cuerpo de
     * clase de 20 líneas. Bajo el threshold 25, no debe reportarse.
     */
    #[Test]
    public function caso_negativo_clase_dentro_del_limite(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Models/ValidSmallUser.php'],
            []
        );
    }

    /**
     * Falso positivo a evitar — `SmallClassWithManyImports` declara
     * ~30 use statements antes de una clase muy pequeña (4 líneas de
     * cuerpo). El total del archivo excede 25 líneas, pero el cuerpo
     * de la clase NO. La implementación previa medía a nivel de
     * FileNode y flageaba; la actual mide a nivel de Class_ y NO debe.
     */
    #[Test]
    public function falso_positivo_clase_pequena_con_muchos_imports(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Models/SmallClassWithManyImports.php'],
            []
        );
    }

    /**
     * Falso negativo a evitar — `MultiClassFatClasses.php` declara dos
     * clases (`FirstFatClass` y `SecondFatClass`) cada una con cuerpo
     * de 31 líneas. Ambas exceden el threshold 25. La implementación
     * previa miraba solo `getRootNode` (la primera clase) y emitía un
     * único error; la actual recorre todas las clases y emite uno por
     * cada una.
     */
    #[Test]
    public function falso_negativo_dos_clases_grandes_en_archivo_multi_clase(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Models/MultiClassFatClasses.php'],
            [
                [
                    sprintf(self::ERROR_MESSAGE_TEMPLATE, 'Opscale\Models\FirstFatClass', 31, self::MAX_LINES),
                    35,
                ],
                [
                    sprintf(self::ERROR_MESSAGE_TEMPLATE, 'Opscale\Models\SecondFatClass', 31, self::MAX_LINES),
                    67,
                ],
            ]
        );
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new MaxLinesRule($reflectionProvider, self::MAX_LINES);
    }
}
