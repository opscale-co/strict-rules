<?php

declare(strict_types=1);

namespace Opscale\Tests\Rules;

use Opscale\Rules\DDD\Aggregates\ParentChildTransactionRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(ParentChildTransactionRule::class)]
class ParentChildTransactionTest extends RuleTestCase
{
    private const ERROR_MESSAGE_TEMPLATE = 'Direct save() on model "%s" is not allowed. '.
        'Models with parent relationships (belongsTo) should only be saved through their parent aggregates.';

    /**
     * Caso positivo — un Repository llama save() sobre un parámetro tipado
     * como un modelo con belongsTo (Product → User). La regla debe reportar
     * exactamente un error en la línea del save().
     */
    #[Test]
    public function caso_positivo_save_directo_en_hijo(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Models/Repositories/ProductRepository.php'],
            [
                [
                    sprintf(self::ERROR_MESSAGE_TEMPLATE, 'Opscale\Models\Product'),
                    12,
                ],
            ]
        );
    }

    /**
     * Caso negativo — un Repository llama save() sobre un parámetro tipado
     * como aggregate root (Tenant) que no declara ninguna relación
     * BelongsTo en su clase ni en ancestros. No debe reportarse error.
     */
    #[Test]
    public function caso_negativo_save_en_aggregate_root(): void
    {
        $this->analyse(
            [
                __DIR__.'/../fixtures/Models/Tenant.php',
                __DIR__.'/../fixtures/Models/Repositories/TenantRepository.php',
            ],
            []
        );
    }

    /**
     * Falso positivo a evitar — el modelo OrgPolicy contiene un método cuyo
     * cuerpo llama $group->belongsTo($actor) (helper no relacional). La
     * implementación previa, que escaneaba cuerpos de métodos en busca de
     * cualquier llamada llamada `belongsTo`, habría flageado este modelo.
     * Con la regla actual (return-type only) NO debe reportarse.
     */
    #[Test]
    public function falso_positivo_belongs_to_helper_no_relacional(): void
    {
        $this->analyse(
            [
                __DIR__.'/../fixtures/Models/OrgPolicy.php',
                __DIR__.'/../fixtures/Models/Repositories/OrgPolicyRepository.php',
            ],
            []
        );
    }

    /**
     * Falso negativo a evitar — InheritingChildEntity hereda parent():
     * BelongsTo de AbstractChildEntity. La implementación previa, que solo
     * inspeccionaba la clase concreta, habría dejado pasar el save(). La
     * regla actual camina la cadena de herencia y debe reportar el error.
     */
    #[Test]
    public function falso_negativo_belongs_to_heredado(): void
    {
        $this->analyse(
            [
                __DIR__.'/../fixtures/Models/AbstractChildEntity.php',
                __DIR__.'/../fixtures/Models/InheritingChildEntity.php',
                __DIR__.'/../fixtures/Models/Repositories/InheritingChildRepository.php',
            ],
            [
                [
                    sprintf(self::ERROR_MESSAGE_TEMPLATE, 'Opscale\Models\InheritingChildEntity'),
                    12,
                ],
            ]
        );
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new ParentChildTransactionRule($reflectionProvider);
    }
}
