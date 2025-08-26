<?php

namespace Opscale\Models;

use Illuminate\Support\Facades\Cache;

class NonModelClass
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCachedData(): mixed
    {
        return Cache::get('data_' . $this->name);
    }
}