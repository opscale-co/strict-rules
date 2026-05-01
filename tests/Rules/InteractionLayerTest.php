<?php

declare(strict_types=1);

namespace Opscale\Tests\Rules;

use Opscale\Rules\CLEAN\Interaction\InteractionLayerRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(InteractionLayerRule::class)]
class InteractionLayerTest extends RuleTestCase
{
    private const FACADE_NOT_ALLOWED_TEMPLATE = 'Clean Architecture violation: Class "%s" from layer 5 cannot depend on "%s". '.
        'This import is not allowed in this layer according to facade, framework, project, or external import rules.';

    /**
     * Caso positivo — un Controller importa la facade `DB`, que pertenece
     * al set permitido de la capa Representation (1), no a Interaction (5).
     * La regla debe reportar la violación.
     */
    #[Test]
    public function caso_positivo_facade_de_capa_inferior_es_violacion(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Http/Controllers/ProductController.php'],
            [
                [
                    sprintf(
                        self::FACADE_NOT_ALLOWED_TEMPLATE,
                        'Opscale\Http\Controllers\ProductController',
                        'Illuminate\Support\Facades\DB'
                    ),
                    7,
                ],
            ]
        );
    }

    /**
     * Caso negativo — el Controller `McpInertiaController` importa
     * exclusivamente cosas permitidas en Interaction tras esta feature:
     * Auth y Log facades, Request type-hint, y los externos Mcp\,
     * PhpMcp\, Laravel\Nova\, Inertia\ y Laravel\Sanctum\.
     */
    #[Test]
    public function caso_negativo_imports_permitidos_incluyendo_mcp_nova_inertia_sanctum(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Http/Controllers/McpInertiaController.php'],
            []
        );
    }

    /**
     * Falso positivo a evitar — los namespaces externos Mcp\, PhpMcp\,
     * Laravel\Nova\, Inertia\ y Laravel\Sanctum\ son librerías que viven
     * naturalmente en la capa de Interaction. Antes de esta feature
     * `allowedExternalImports` estaba vacío y cualquier uso de estas
     * librerías era flageado. Ahora NO debe reportarse.
     */
    #[Test]
    public function falso_positivo_mcp_nova_inertia_sanctum_no_son_violaciones(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Http/Controllers/McpInertiaController.php'],
            []
        );
    }

    /**
     * Falso negativo a evitar — la relajación introducida en esta feature
     * NO debe abrir la puerta a facades de capas inferiores. Un Controller
     * que importa la facade `DB` (Representation) sigue siendo violación.
     */
    #[Test]
    public function falso_negativo_facade_de_otra_capa_sigue_siendo_violacion(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Http/Controllers/DbAccessController.php'],
            [
                [
                    sprintf(
                        self::FACADE_NOT_ALLOWED_TEMPLATE,
                        'Opscale\Http\Controllers\DbAccessController',
                        'Illuminate\Support\Facades\DB'
                    ),
                    6,
                ],
            ]
        );
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new InteractionLayerRule($reflectionProvider);
    }
}
