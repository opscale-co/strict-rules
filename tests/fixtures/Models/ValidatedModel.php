<?php

namespace Opscale\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Opscale\Validations\Validatable;

class ValidatedModel extends Model
{
    use HasUlids;
    use Validatable;

    protected $fillable = [
        'name',
        'description',
    ];
}
