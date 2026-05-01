<?php

namespace Opscale\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class CarbonTypedModel extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'birth_date',
    ];

    protected ?Carbon $birthDate = null;
}
