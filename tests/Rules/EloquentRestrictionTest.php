<?php

declare(strict_types=1);

namespace Opscale\Tests\Rules;

use Opscale\Rules\DDD\Repositories\EloquentRestrictionRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(EloquentRestrictionRule::class)]
class EloquentRestrictionTest extends RuleTestCase
{
    private const ERROR_MESSAGE_TEMPLATE = 'Eloquent calls are only allowed within '.
        '`\\Models\\Repositories\\*` or `\\Services\\*`. Found "%s" call in "%s".';

    /**
     * Caso positivo — un modelo Eloquent (Product) ejecuta `self::where`
     * y `$this->where`. Está fuera de las dos ubicaciones permitidas
     * (no es Repository ni Service), por lo que la regla debe reportar
     * ambas llamadas CRUD. La declaración de relación `$this->belongsTo`
     * en la línea 41 NO se reporta: las relaciones quedan fuera del
     * alcance de la regla, que solo cubre operaciones CRUD.
     */
    #[Test]
    public function caso_positivo_eloquent_calls_en_modelo(): void
    {
        $this->analyse(
            [
                __DIR__.'/../fixtures/Models/Repositories/UserRepository.php',
                __DIR__.'/../fixtures/Models/Product.php',
            ],
            [
                [sprintf(self::ERROR_MESSAGE_TEMPLATE, 'where', 'Opscale\Models\Product'), 14],
                [sprintf(self::ERROR_MESSAGE_TEMPLATE, 'where', 'Opscale\Models\Product'), 19],
            ]
        );
    }

    /**
     * Caso negativo — un Repository trait bajo \Models\Repositories\* y
     * una clase Service bajo \Services\* hacen llamadas Eloquent. Ambos
     * namespaces están exentos.
     */
    #[Test]
    public function caso_negativo_eloquent_calls_en_repository_y_service(): void
    {
        $this->analyse(
            [
                __DIR__.'/../fixtures/Models/Repositories/ProductRepository.php',
                __DIR__.'/../fixtures/Services/CrossEntityService.php',
            ],
            []
        );
    }

    /**
     * Falso positivo a evitar — la clase Locator (no-Eloquent) tiene un
     * método `find` propio y otro método que llama `self::find($key)`.
     * Tras ampliar el scope a clases fuera de \Models\*, una
     * implementación ingenua flagearía. La regla actual solo trata el
     * static-self call como Eloquent cuando la clase contenedora es un
     * Eloquent Model — Locator no lo es, no flag.
     */
    #[Test]
    public function falso_positivo_self_find_en_clase_no_eloquent(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Domain/Locator.php'],
            []
        );
    }

    /**
     * Falso positivo a evitar — un modelo Eloquent declara relaciones
     * (`belongsTo`, `hasMany`), eager-load (`load`) y helpers de estado
     * (`getAttributes`, `toArray`, `refresh`). La iteración previa de la
     * regla cubría todas estas APIs y producía falsos positivos: las
     * relaciones y el estado del modelo son responsabilidad legítima
     * del propio Model y no operaciones CRUD. La regla actual solo
     * cubre CRUD, por lo que ninguna llamada se reporta.
     */
    #[Test]
    public function falso_positivo_relaciones_y_estado_en_modelo(): void
    {
        $this->analyse(
            [__DIR__.'/../fixtures/Models/RelationsOnlyModel.php'],
            []
        );
    }

    /**
     * Falso negativo a evitar — un Controller fuera de \Services\*
     * llama `User::find($id)`. La implementación previa, restringida a
     * \Models\*, ni siquiera procesaba este archivo. La regla actual
     * lo detecta y reporta.
     */
    #[Test]
    public function falso_negativo_eloquent_call_en_controller(): void
    {
        $this->analyse(
            [
                __DIR__.'/../fixtures/Models/User.php',
                __DIR__.'/../fixtures/Http/UserController.php',
            ],
            [
                [sprintf(self::ERROR_MESSAGE_TEMPLATE, 'find', 'Opscale\Http\UserController'), 11],
            ]
        );
    }

    protected function getRule(): Rule
    {
        $reflectionProvider = $this->createReflectionProvider();

        return new EloquentRestrictionRule($reflectionProvider);
    }
}
