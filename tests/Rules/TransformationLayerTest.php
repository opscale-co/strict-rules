<?php

declare(strict_types=1);

namespace Opscale\Tests\Rules;

use Opscale\Rules\CLEAN\Transformation\TransformationLayerRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(TransformationLayerRule::class)]
class TransformationLayerTest extends RuleTestCase
{
    private const IMPORT_NOT_ALLOWED_TEMPLATE = 'Clean Architecture violation: Class "%s" from layer 3 cannot depend on "%s". '.
        'This import is not allowed in this layer according to facade, framework, project, or external import rules.';

    private const LAYER_VIOLATION_TEMPLATE = 'Clean Architecture violation: Class "%s" from layer 3 cannot depend on "%s" from layer %d. '.
        'Layers can only use equal or lower layers and communicate via events upwards.';

    /**
     * Caso positivo — un Service importa la facade `Response` (Interaction)
     * y un Job (`CleanOldProducts`, layer 4). Ambos son violaciones hacia
     * capas superiores y deben ser reportadas.
     */
    #[Test]
    public function caso_positivo_facade_y_dependencia_capa_superior(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Services/ExternalAPIService.php'],
            [
                [
                    sprintf(
                        self::IMPORT_NOT_ALLOWED_TEMPLATE,
                        'Opscale\Services\ExternalAPIService',
                        'Illuminate\Support\Facades\Response'
                    ),
                    8,
                ],
                [
                    sprintf(
                        self::LAYER_VIOLATION_TEMPLATE,
                        'Opscale\Services\ExternalAPIService',
                        'Opscale\Jobs\CleanOldProducts',
                        4
                    ),
                    9,
                ],
            ]
        );
    }

    /**
     * Caso negativo — el Service `ServiceUsingHelpers` importa los
     * helpers de `Illuminate\Support` (Arr, Collection, Number, Str),
     * la facade Log y un modelo del proyecto. Tras añadir los helpers
     * específicos al rule y `Log` a las facades, NO debe reportar.
     */
    #[Test]
    public function caso_negativo_service_con_log_y_support_helpers(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Services/ServiceUsingHelpers.php'],
            []
        );
    }

    /**
     * Falso positivo a evitar — los helpers de Illuminate\Support
     * (Arr, Collection, Number, Str) son las herramientas canónicas de
     * transformación de datos en Laravel. Antes de esta feature
     * `allowedFrameworkImports` no los incluía y los flageaba. Ahora NO.
     */
    #[Test]
    public function falso_positivo_support_helpers_no_son_violaciones(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Services/ServiceUsingHelpers.php'],
            []
        );
    }

    /**
     * Falso negativo a evitar — la relajación NO debe permitir facades
     * de capas inferiores no asociadas a Transformation. Se usaron
     * prefijos de clase específicos (no `Illuminate\Support\`) para
     * evitar que `Illuminate\Support\Facades\DB` se cuele por la
     * comparación `str_starts_with`. Un Service que importa la facade
     * `DB` (Representation) sigue siendo violación.
     */
    #[Test]
    public function falso_negativo_facade_de_otra_capa_sigue_siendo_violacion(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Services/DbAccessService.php'],
            [
                [
                    sprintf(
                        self::IMPORT_NOT_ALLOWED_TEMPLATE,
                        'Opscale\Services\DbAccessService',
                        'Illuminate\Support\Facades\DB'
                    ),
                    5,
                ],
            ]
        );
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new TransformationLayerRule($reflectionProvider);
    }
}
