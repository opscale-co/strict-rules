<?php

namespace Opscale\Models\DTOs;

class NonValueObject
{
    public function __construct(
        private string $name,
        private int $value
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function format(): string
    {
        return sprintf('%s: %d', $this->name, $this->value);
    }
}