<?php

namespace Opscale\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class DeclarativeAccessorModel extends Model
{
    protected $fillable = [
        'name',
    ];

    protected function name(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): string {
                if ($value === null) {
                    return 'Anonymous';
                }

                return ucfirst($value);
            },
        );
    }
}
