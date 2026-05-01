<?php

namespace Opscale\Models;

class ValidatedChild extends ValidatedAggregateRoot
{
    protected $fillable = [
        'name',
    ];
}
