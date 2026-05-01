<?php

namespace Opscale\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractUlidEntity extends Model
{
    use HasUlids;
}
