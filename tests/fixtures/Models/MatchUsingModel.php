<?php

namespace Opscale\Models;

use Illuminate\Database\Eloquent\Model;

class MatchUsingModel extends Model
{
    protected $fillable = [
        'state',
    ];

    public function status(): string
    {
        return match ($this->state) {
            'active' => 'A',
            'inactive' => 'I',
            default => 'U',
        };
    }
}
