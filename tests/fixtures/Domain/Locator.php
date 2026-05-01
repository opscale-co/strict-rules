<?php

namespace Opscale\Domain;

class Locator
{
    public static function find(string $key): ?string
    {
        return $key !== '' ? $key : null;
    }

    public function locate(string $key): ?string
    {
        return self::find($key);
    }
}
