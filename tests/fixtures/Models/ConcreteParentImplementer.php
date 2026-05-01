<?php

namespace Opscale\Models;

use Opscale\Contracts\InheritedContract;

class ConcreteParentImplementer implements InheritedContract
{
    public function inheritedMethod(): string
    {
        return 'parent implementation: '.static::class;
    }
}
