<?php

namespace Opscale\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

abstract class AbstractChildEntity extends Model
{
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
