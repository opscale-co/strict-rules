<?php

declare(strict_types=1);

namespace Opscale\Tests\Rules;

use Opscale\Rules\CLEAN\Representation\RepresentationLayerRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(RepresentationLayerRule::class)]
class RepresentationLayerTest extends RuleTestCase
{
    private const IMPORT_NOT_ALLOWED_TEMPLATE = 'Clean Architecture violation: Class "%s" from layer 1 cannot depend on "%s". '.
        'This import is not allowed in this layer according to facade, framework, project, or external import rules.';

    private const LAYER_VIOLATION_TEMPLATE = 'Clean Architecture violation: Class "%s" from layer 1 cannot depend on "%s" from layer %d. '.
        'Layers can only use equal or lower layers and communicate via events upwards.';

    /**
     * Caso positivo — el modelo `User` importa un Job (layer 4), la
     * facade Storage (Transformation), `Illuminate\Support\Str` (no
     * permitido en Representation) y `Illuminate\Http\Request`
     * (Interaction). Las cuatro deben reportarse como violaciones.
     */
    #[Test]
    public function caso_positivo_imports_de_capas_superiores(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Models/User.php'],
            [
                [
                    sprintf(
                        self::LAYER_VIOLATION_TEMPLATE,
                        'Opscale\Models\User',
                        'Opscale\Jobs\CleanOldProducts',
                        4
                    ),
                    8,
                ],
                [
                    sprintf(
                        self::IMPORT_NOT_ALLOWED_TEMPLATE,
                        'Opscale\Models\User',
                        'Illuminate\Support\Facades\Storage'
                    ),
                    9,
                ],
                [
                    sprintf(
                        self::IMPORT_NOT_ALLOWED_TEMPLATE,
                        'Opscale\Models\User',
                        'Illuminate\Support\Str'
                    ),
                    10,
                ],
                [
                    sprintf(
                        self::IMPORT_NOT_ALLOWED_TEMPLATE,
                        'Opscale\Models\User',
                        'Illuminate\Http\Request'
                    ),
                    11,
                ],
            ]
        );
    }

    /**
     * Caso negativo — el modelo `CarbonTypedModel` usa el trait HasUlids,
     * extiende Model y tipa una propiedad con
     * `Illuminate\Support\Carbon`. Tras añadir Carbon al rule, NO debe
     * reportarse.
     */
    #[Test]
    public function caso_negativo_modelo_con_traits_y_carbon(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Models/CarbonTypedModel.php'],
            []
        );
    }

    /**
     * Falso positivo a evitar — `Illuminate\Support\Carbon` es la
     * utilidad de fechas que Laravel envuelve, idiomática para tipar
     * propiedades de fecha en modelos. Antes de esta feature
     * `allowedFrameworkImports` no la incluía y la regla la flageaba.
     * Ahora NO debe reportarse.
     */
    #[Test]
    public function falso_positivo_carbon_typing_no_es_violacion(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Models/CarbonTypedModel.php'],
            []
        );
    }

    /**
     * Falso negativo a evitar — la relajación introducida (Carbon
     * específico, sin el broader `Illuminate\Support\`) NO debe abrir
     * la puerta a otras facades. Un modelo que importa la facade
     * `Storage` (Transformation) sigue siendo violación.
     */
    #[Test]
    public function falso_negativo_facade_de_otra_capa_sigue_siendo_violacion(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Models/StorageUsingModel.php'],
            [
                [
                    sprintf(
                        self::IMPORT_NOT_ALLOWED_TEMPLATE,
                        'Opscale\Models\StorageUsingModel',
                        'Illuminate\Support\Facades\Storage'
                    ),
                    6,
                ],
            ]
        );
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new RepresentationLayerRule($reflectionProvider);
    }
}
