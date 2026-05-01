<?php

namespace Opscale\Models;

use Illuminate\Database\Eloquent\Model;
use Opscale\Pretenders\Validatable;

class PretendingValidatedModel extends Model
{
    use Validatable;

    protected $fillable = [
        'name',
    ];
}
