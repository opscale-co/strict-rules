<?php

namespace Opscale\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RelationsOnlyModel extends Model
{
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function eagerWithRelations()
    {
        return $this->load('owner', 'items');
    }

    public function attributesArray(): array
    {
        return $this->getAttributes();
    }

    public function asPayload(): array
    {
        return $this->toArray();
    }

    public function refreshState(): void
    {
        $this->refresh();
    }
}
