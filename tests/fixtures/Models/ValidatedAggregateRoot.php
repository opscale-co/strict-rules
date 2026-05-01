<?php

namespace Opscale\Models;

use Illuminate\Database\Eloquent\Model;
use Opscale\Validations\Validatable;

abstract class ValidatedAggregateRoot extends Model
{
    use Validatable;
}
