<?php

declare(strict_types=1);

namespace Opscale\Tests\Rules;

use Opscale\Rules\CLEAN\Orchestration\OrchestrationLayerRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(OrchestrationLayerRule::class)]
class OrchestrationLayerTest extends RuleTestCase
{
    private const IMPORT_NOT_ALLOWED_TEMPLATE = 'Clean Architecture violation: Class "%s" from layer 4 cannot depend on "%s". '.
        'This import is not allowed in this layer according to facade, framework, project, or external import rules.';

    private const LAYER_VIOLATION_TEMPLATE = 'Clean Architecture violation: Class "%s" from layer 4 cannot depend on "%s" from layer %d. '.
        'Layers can only use equal or lower layers and communicate via events upwards.';

    /**
     * Caso positivo — un Job importa un Controller (`ProductsController`,
     * layer 5) y la facade `Http` (Interaction). Ambos son violaciones
     * hacia capas superiores y deben ser reportadas.
     */
    #[Test]
    public function caso_positivo_dependencia_capa_superior_y_facade_no_permitida(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Jobs/CleanOldProducts.php'],
            [
                [
                    sprintf(
                        self::LAYER_VIOLATION_TEMPLATE,
                        'Opscale\Jobs\CleanOldProducts',
                        'Opscale\Http\Controllers\ProductsController',
                        5
                    ),
                    10,
                ],
                [
                    sprintf(
                        self::IMPORT_NOT_ALLOWED_TEMPLATE,
                        'Opscale\Jobs\CleanOldProducts',
                        'Illuminate\Support\Facades\Http'
                    ),
                    11,
                ],
            ]
        );
    }

    /**
     * Caso negativo — el Job `ValidJob` importa Bus / Queue / Foundation
     * para queueing, el modelo Eloquent base para tipado, la facade Log
     * y un modelo del proyecto. Tras añadir
     * `Illuminate\Database\Eloquent\` y `Log` al rule, NO debe reportar.
     */
    #[Test]
    public function caso_negativo_job_con_log_y_eloquent_typing(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Jobs/ValidJob.php'],
            []
        );
    }

    /**
     * Falso positivo a evitar — el tipo `Illuminate\Database\Eloquent\Model`
     * usado para type-hint en parámetros de Job es el idiom canónico.
     * Antes de esta feature `allowedFrameworkImports` no incluía
     * `Illuminate\Database\Eloquent\` y la regla lo flageaba. Ahora NO.
     */
    #[Test]
    public function falso_positivo_eloquent_model_typing_no_es_violacion(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Jobs/ValidJob.php'],
            []
        );
    }

    /**
     * Falso negativo a evitar — la relajación NO debe permitir facades
     * de capas inferiores no asociadas a Orchestration. Un Job que
     * importa la facade `DB` (Representation) sigue siendo violación.
     */
    #[Test]
    public function falso_negativo_facade_de_otra_capa_sigue_siendo_violacion(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Jobs/DbAccessJob.php'],
            [
                [
                    sprintf(
                        self::IMPORT_NOT_ALLOWED_TEMPLATE,
                        'Opscale\Jobs\DbAccessJob',
                        'Illuminate\Support\Facades\DB'
                    ),
                    8,
                ],
            ]
        );
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new OrchestrationLayerRule($reflectionProvider);
    }
}
