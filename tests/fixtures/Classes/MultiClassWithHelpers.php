<?php

namespace Opscale\Classes;

class FirstCleanClass
{
    public function __construct(private readonly object $service) {}

    public function process(): mixed
    {
        return $this->service;
    }
}

class SecondHelperClass
{
    public function process(): mixed
    {
        return cache()->get('key');
    }
}
