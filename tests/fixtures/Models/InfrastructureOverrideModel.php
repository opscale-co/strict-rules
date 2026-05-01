<?php

namespace Opscale\Models;

use Illuminate\Database\Eloquent\Model;

class InfrastructureOverrideModel extends Model
{
    protected $fillable = [
        'name',
    ];

    /**
     * Override of Eloquent's framework-level getter — NOT a custom
     * accessor on a specific attribute name.
     */
    public function getAttribute($key)
    {
        return parent::getAttribute($key);
    }

    /**
     * Override of Eloquent's framework-level setter — NOT a custom
     * mutator on a specific attribute name.
     */
    public function setAttribute($key, $value)
    {
        return parent::setAttribute($key, $value);
    }
}
