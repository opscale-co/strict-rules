<?php

namespace Opscale\Models;

use Illuminate\Database\Eloquent\Model;

class FirstCleanModel extends Model
{
    protected $fillable = [
        'name',
    ];
}

class SecondModelWithMutator extends Model
{
    protected $fillable = [
        'name',
    ];

    public function getNameAttribute(string $value): string
    {
        return strtoupper($value);
    }
}
