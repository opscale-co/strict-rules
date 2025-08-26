<?php

namespace Opscale\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class SimpleModel extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
    ];
}