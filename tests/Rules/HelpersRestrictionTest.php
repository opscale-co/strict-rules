<?php

declare(strict_types=1);

namespace Opscale\Tests\Rules;

use Opscale\Rules\Smells\HelpersRestrictionRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(HelpersRestrictionRule::class)]
class HelpersRestrictionTest extends RuleTestCase
{
    private const CHAINED_TEMPLATE = 'Helper function "%s()->%s()" usage detected in "%s". '.
        'Consider injecting the service directly instead of using helper functions.';

    private const STANDALONE_TEMPLATE = 'Helper function "%s()" usage detected in "%s". '.
        'Consider injecting the service directly instead of using helper functions.';

    /**
     * Caso positivo — `ClassWithHelpers` usa `auth()->user()`,
     * `cache()->get(...)` y `config(...)`. La regla debe reportar las
     * tres ocurrencias.
     */
    #[Test]
    public function caso_positivo_helpers_chained_y_standalone(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Classes/ClassWithHelpers.php'],
            [
                [sprintf(self::CHAINED_TEMPLATE, 'auth', 'user', 'Opscale\Classes\ClassWithHelpers'), 9],
                [sprintf(self::CHAINED_TEMPLATE, 'cache', 'get', 'Opscale\Classes\ClassWithHelpers'), 10],
                [sprintf(self::STANDALONE_TEMPLATE, 'config', 'Opscale\Classes\ClassWithHelpers'), 11],
            ]
        );
    }

    /**
     * Caso negativo — `ClassWithoutHelpers` usa servicios inyectados
     * por constructor, sin llamadas a helpers globales.
     */
    #[Test]
    public function caso_negativo_clase_sin_helpers(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Classes/ClassWithoutHelpers.php'],
            []
        );
    }

    /**
     * Falso positivo a evitar — `ClassWithStaticMethods` usa solo
     * llamadas estáticas y facades (`Auth::user()`, `Cache::get()`,
     * etc.). Estas son `StaticCall` nodes, no `FuncCall`. La regla
     * solo flagea funciones globales — los static calls y facades
     * NO deben reportarse.
     */
    #[Test]
    public function falso_positivo_static_calls_y_facades_no_son_helpers(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Classes/ClassWithStaticMethods.php'],
            []
        );
    }

    /**
     * Falso negativo a evitar — `MultiClassWithHelpers.php` declara
     * dos clases. La primera (`FirstCleanClass`) usa DI; la segunda
     * (`SecondHelperClass`) usa `cache()->get('key')`. La
     * implementación previa miraba solo `getRootNode` (la primera) y
     * no reportaba; la regla actual recorre todas las clases del
     * archivo y reporta el helper de la segunda.
     */
    #[Test]
    public function falso_negativo_helpers_en_segunda_clase_de_archivo_multi_clase(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Classes/MultiClassWithHelpers.php'],
            [
                [
                    sprintf(self::CHAINED_TEMPLATE, 'cache', 'get', 'Opscale\Classes\SecondHelperClass'),
                    19,
                ],
            ]
        );
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new HelpersRestrictionRule($reflectionProvider);
    }
}
