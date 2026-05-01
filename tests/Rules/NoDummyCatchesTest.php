<?php

declare(strict_types=1);

namespace Opscale\Tests\Rules;

use Opscale\Rules\Smells\NoDummyCatchesRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(NoDummyCatchesRule::class)]
class NoDummyCatchesTest extends RuleTestCase
{
    private const EMPTY_TEMPLATE = 'Empty catch block for exception type(s) "%s". '.
        'Either handle the exception properly or remove the try-catch block.';

    /**
     * Caso positivo — `CleanOldProducts` tiene un `catch (\Exception $e) { }`
     * con cuerpo vacío. La regla debe reportarlo.
     */
    #[Test]
    public function caso_positivo_catch_vacio(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Jobs/CleanOldProducts.php'],
            [
                [sprintf(self::EMPTY_TEMPLATE, 'Exception'), 25],
            ]
        );
    }

    /**
     * Caso negativo — `ValidExceptionHandling` maneja la excepción con
     * lógica sustantiva (logging, condicionales). No debe reportarse.
     */
    #[Test]
    public function caso_negativo_handling_completo(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Jobs/ValidExceptionHandling.php'],
            []
        );
    }

    /**
     * Falso positivo a evitar — `WrappingExceptionJob` tiene un catch
     * con un único `throw new RuntimeException(..., 0, $e)`. Esto es
     * wrapping legítimo: agrega contexto y preserva la causa original.
     * La implementación previa lo flageaba como dummy single-throw; la
     * regla actual exenta los `throw new ...` por ser wrapping.
     */
    #[Test]
    public function falso_positivo_throw_wrapping_no_es_dummy(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Jobs/WrappingExceptionJob.php'],
            []
        );
    }

    /**
     * Falso negativo a evitar — `MultiClassDummyCatch.php` declara dos
     * clases. La primera (`FirstHandledJob`) hace logging dentro del
     * catch. La segunda (`SecondDummyJob`) tiene un catch vacío. La
     * implementación previa miraba solo `getRootNode` (la primera) y
     * no reportaba; la regla actual recorre todas las clases y
     * reporta el catch vacío de la segunda.
     */
    #[Test]
    public function falso_negativo_segundo_class_con_catch_vacio_en_archivo_multi_clase(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Jobs/MultiClassDummyCatch.php'],
            [
                [sprintf(self::EMPTY_TEMPLATE, 'Exception'), 27],
            ]
        );
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new NoDummyCatchesRule($reflectionProvider);
    }
}
