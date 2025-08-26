<?php

namespace Opscale\Contracts;

interface TestInterface
{
    public function process(array $data): array;
    
    public function validate(mixed $input): bool;
    
    public function transform(string $value): string;
}