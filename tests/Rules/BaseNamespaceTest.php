<?php

declare(strict_types=1);

namespace Opscale\Tests\Rules;

use Opscale\Rules\DDD\Subdomains\BaseNamespaceRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(BaseNamespaceRule::class)]
class BaseNamespaceTest extends RuleTestCase
{
    private const ERROR_MESSAGE_TEMPLATE = 'Class "%s" extends Eloquent Model but is not located '.
        'directly under a "\\Models" namespace. Move the class so its file-level namespace '.
        'ends with "\\Models" (no subfolders allowed under Models).';

    /**
     * Caso positivo — un modelo Eloquent (Domain\User) cuya namespace no
     * termina en `\Models` debe ser reportado.
     */
    #[Test]
    public function caso_positivo_modelo_eloquent_fuera_de_models(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Domain/User.php'],
            [
                [sprintf(self::ERROR_MESSAGE_TEMPLATE, 'Opscale\Domain\User'), 11],
            ]
        );
    }

    /**
     * Caso negativo — un modelo Eloquent (Models\ValidUlidUser) cuya
     * namespace termina en `\Models` no debe ser reportado.
     */
    #[Test]
    public function caso_negativo_modelo_eloquent_en_models(): void
    {
        $this->analyse([__DIR__.'/../fixtures/Models/ValidUlidUser.php'], []);
    }

    /**
     * Falso positivo a evitar — una clase no-Eloquent (`Domain\JustAHelper`)
     * vive fuera de `\Models`. La regla debe ignorarla por completo: solo
     * los modelos Eloquent están sujetos al check de namespace.
     */
    #[Test]
    public function falso_positivo_clase_no_eloquent_fuera_de_models(): void
    {
        $this->analyse([__DIR__.'/../fixtures/Domain/JustAHelper.php'], []);
    }

    /**
     * Falso negativo a evitar — un archivo declara dos clases bajo
     * `\Opscale\Domain`: la primera es un helper no-Eloquent y la segunda
     * extiende `Model`. La implementación previa miraba solo la primera
     * clase vía `getClassReflection` y el archivo entero quedaba fuera
     * del scope. La regla actual recorre todas las clases y reporta la
     * segunda.
     */
    #[Test]
    public function falso_negativo_segunda_clase_eloquent_en_archivo_multi_clase(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Domain/MultiClassFile.php'],
            [
                [sprintf(self::ERROR_MESSAGE_TEMPLATE, 'Opscale\Domain\SecondModel'), 15],
            ]
        );
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new BaseNamespaceRule($reflectionProvider);
    }
}
