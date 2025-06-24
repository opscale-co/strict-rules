<?php

namespace Opscale\Models;

use Illuminate\Database\Eloquent\Model;
use Opscale\Jobs\CleanOldProducts;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Product extends Model
{
    final public static function firstAvailable()
    {
        return self::where('available', true)->first();
    } 

    public function isInStock()
    {
        return $this->where('stock', '>', 0)->exists();
    }

    public final function getIdAttribute()
    {
        return $this->attributes['id'];
    }

    public final function setIdAttribute(int $id)
    {
        $this->attributes['id'] = $id;
    }

    protected final function stock(): Attribute
    {
        return Attribute::make(
            get: fn (int $value) => $value > 0 ? 'In Stock' : 'Out of Stock',
        );
    }

    public final function user() : \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
