<?php

namespace Opscale\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class DisabledUlidModel extends Model
{
    use HasUlids;

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'name',
    ];
}
