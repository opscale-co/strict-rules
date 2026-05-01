<?php

namespace Opscale\Models;

use Illuminate\Database\Eloquent\Model;

class FirstFinalClass extends Model
{
    final public function getName(): string
    {
        return 'first';
    }
}

class SecondNonFinalClass extends Model
{
    public function getName(): string
    {
        return 'second';
    }
}
