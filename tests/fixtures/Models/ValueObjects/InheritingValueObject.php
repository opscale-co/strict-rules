<?php

namespace Opscale\Models\ValueObjects;

use Illuminate\Database\Eloquent\Model;

class InheritingValueObject extends AbstractCastableValueObject
{
    public function __construct(public readonly string $value = '') {}

    public function get(Model $model, string $key, mixed $value, array $attributes): self
    {
        return new self((string) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        return $value instanceof self ? $value->value : (string) $value;
    }
}
