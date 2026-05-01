<?php

namespace Opscale\Models;

use RuntimeException;

class StubChildOverrider extends ConcreteParentImplementer
{
    public function inheritedMethod(): string
    {
        throw new RuntimeException('not implemented');
    }
}
