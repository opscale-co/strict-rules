<?php

declare(strict_types=1);

namespace Opscale\Tests\Rules;

use Opscale\Rules\CLEAN\Communication\CommunicationLayerRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(CommunicationLayerRule::class)]
class CommunicationLayerTest extends RuleTestCase
{
    private const IMPORT_NOT_ALLOWED_TEMPLATE = 'Clean Architecture violation: Class "%s" from layer 2 cannot depend on "%s". '.
        'This import is not allowed in this layer according to facade, framework, project, or external import rules.';

    private const LAYER_VIOLATION_TEMPLATE = 'Clean Architecture violation: Class "%s" from layer 2 cannot depend on "%s" from layer %d. '.
        'Layers can only use equal or lower layers and communicate via events upwards.';

    /**
     * Caso positivo — un Observer importa la facade `Response` (Interaction)
     * y un Job (`Opscale\Jobs\CleanOldProducts`, layer 4). Ambos son
     * dependencias hacia capas superiores y deben ser reportadas.
     */
    #[Test]
    public function caso_positivo_facade_y_dependencia_capa_superior(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Observers/ProductObserver.php'],
            [
                [
                    sprintf(
                        self::IMPORT_NOT_ALLOWED_TEMPLATE,
                        'Opscale\Observers\ProductObserver',
                        'Illuminate\Support\Facades\Response'
                    ),
                    6,
                ],
                [
                    sprintf(
                        self::LAYER_VIOLATION_TEMPLATE,
                        'Opscale\Observers\ProductObserver',
                        'Opscale\Jobs\CleanOldProducts',
                        4
                    ),
                    7,
                ],
            ]
        );
    }

    /**
     * Caso negativo — el Observer `ValidObserver` importa el contract de
     * Broadcasting, el modelo Eloquent base para tipado, las facades
     * permitidas (Log, Event, Broadcast) y un modelo del proyecto. Tras
     * añadir `Illuminate\Database\Eloquent\` y `Log` al rule, NO debe
     * reportar nada.
     */
    #[Test]
    public function caso_negativo_observer_con_log_event_broadcast_y_eloquent_typing(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Observers/ValidObserver.php'],
            []
        );
    }

    /**
     * Falso positivo a evitar — el tipo `Illuminate\Database\Eloquent\Model`
     * usado para type-hint en métodos de Observer es el idiom canónico.
     * Antes de esta feature `allowedFrameworkImports` no incluía
     * `Illuminate\Database\Eloquent\` y la regla lo flageaba. Ahora NO.
     */
    #[Test]
    public function falso_positivo_eloquent_model_typing_no_es_violacion(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Observers/ValidObserver.php'],
            []
        );
    }

    /**
     * Falso negativo a evitar — la relajación NO debe permitir facades
     * de capas inferiores no asociadas a Communication. Un Observer que
     * importa la facade `DB` (Representation) sigue siendo violación.
     */
    #[Test]
    public function falso_negativo_facade_de_otra_capa_sigue_siendo_violacion(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Observers/DbAccessObserver.php'],
            [
                [
                    sprintf(
                        self::IMPORT_NOT_ALLOWED_TEMPLATE,
                        'Opscale\Observers\DbAccessObserver',
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

        return new CommunicationLayerRule($reflectionProvider);
    }
}
