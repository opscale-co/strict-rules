<?php

declare(strict_types=1);

namespace Opscale\Tests\Rules;

use Opscale\Rules\DDD\ValueObjects\NoAccesorMutatorRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(NoAccesorMutatorRule::class)]
class NoAccesorMutatorTest extends RuleTestCase
{
    private const ERROR_MESSAGE_TEMPLATE = 'Model "%s" is defining "%s" and it should not contain Eloquent '.
        'mutators or accessors. Custom attribute logic should be defined as a ValueObject.';

    /**
     * Caso positivo — el modelo `Product` define un getAttribute
     * personalizado, un setAttribute personalizado y un método estilo
     * Laravel 9+ que retorna `Attribute`. La regla debe reportar las
     * tres ocurrencias.
     */
    #[Test]
    public function caso_positivo_modelo_con_accesores_mutadores_y_attribute(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Models/Product.php'],
            [
                [sprintf(self::ERROR_MESSAGE_TEMPLATE, 'Opscale\Models\Product', 'getIdAttribute'), 22],
                [sprintf(self::ERROR_MESSAGE_TEMPLATE, 'Opscale\Models\Product', 'setIdAttribute'), 27],
                [sprintf(self::ERROR_MESSAGE_TEMPLATE, 'Opscale\Models\Product', 'stock'), 32],
            ]
        );
    }

    /**
     * Caso negativo — un modelo con métodos puramente declarativos
     * (`casts()`, `$fillable`, `$hidden`) y sin accesores ni mutadores
     * personalizados no debe ser reportado.
     */
    #[Test]
    public function caso_negativo_modelo_sin_accesores_ni_mutadores(): void
    {
        $this->analyse([__DIR__.'/../fixtures/Models/ValidUlidUser.php'], []);
    }

    /**
     * Falso positivo a evitar — la clase `InfrastructureOverrideModel`
     * sobrescribe los métodos infraestructurales `getAttribute($key)`
     * y `setAttribute($key, $value)` de Eloquent (NO accesores
     * personalizados). La implementación previa los flageaba por
     * empezar con `set/get` y terminar en `Attribute`. La regla actual
     * exige el patrón canónico `set<Name>Attribute` y los respeta.
     */
    #[Test]
    public function falso_positivo_overrides_de_metodos_eloquent_infraestructura(): void
    {
        $this->analyse([__DIR__.'/../fixtures/Models/InfrastructureOverrideModel.php'], []);
    }

    /**
     * Falso negativo a evitar — un archivo declara dos modelos: el
     * primero limpio, el segundo define `getNameAttribute`. La
     * implementación previa miraba solo la primera clase y dejaba
     * pasar al segundo. La regla actual recorre todas las clases del
     * archivo y reporta el accessor del segundo modelo.
     */
    #[Test]
    public function falso_negativo_mutador_en_segunda_clase_de_archivo_multi_clase(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Models/MultiModelWithMutator.php'],
            [
                [sprintf(self::ERROR_MESSAGE_TEMPLATE, 'Opscale\Models\SecondModelWithMutator', 'getNameAttribute'), 20],
            ]
        );
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new NoAccesorMutatorRule($reflectionProvider);
    }
}
