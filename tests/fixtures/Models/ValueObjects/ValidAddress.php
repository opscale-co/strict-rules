<?php

namespace Opscale\Models\ValueObjects;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class ValidAddress implements CastsAttributes
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $state,
        public readonly string $zipCode,
        public readonly string $country
    ) {}

    public function get(Model $model, string $key, $value, array $attributes): ?self
    {
        if ($value === null) {
            return null;
        }

        $data = json_decode($value, true);
        
        return new self(
            $data['street'] ?? '',
            $data['city'] ?? '',
            $data['state'] ?? '',
            $data['zip_code'] ?? '',
            $data['country'] ?? ''
        );
    }

    public function set(Model $model, string $key, $value, array $attributes): string
    {
        if ($value === null) {
            return json_encode(null);
        }

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if ($value instanceof self) {
            return json_encode([
                'street' => $value->street,
                'city' => $value->city,
                'state' => $value->state,
                'zip_code' => $value->zipCode,
                'country' => $value->country,
            ]);
        }

        return json_encode($value);
    }

    public function getFullAddress(): string
    {
        return implode(', ', array_filter([
            $this->street,
            $this->city,
            $this->state,
            $this->zipCode,
            $this->country
        ]));
    }
}