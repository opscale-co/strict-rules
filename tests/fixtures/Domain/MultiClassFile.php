<?php

namespace Opscale\Domain;

use Illuminate\Database\Eloquent\Model;

class FirstHelper
{
    public function describe(): string
    {
        return 'First helper, not Eloquent.';
    }
}

class SecondModel extends Model
{
    protected $fillable = [
        'name',
    ];
}
