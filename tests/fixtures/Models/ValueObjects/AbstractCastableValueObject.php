<?php

namespace Opscale\Models\ValueObjects;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Abstract base for shared Value Object behaviour.
 *
 * @implements CastsAttributes<mixed, mixed>
 */
abstract class AbstractCastableValueObject implements CastsAttributes
{
    abstract public function get(Model $model, string $key, mixed $value, array $attributes): mixed;

    abstract public function set(Model $model, string $key, mixed $value, array $attributes): mixed;
}
