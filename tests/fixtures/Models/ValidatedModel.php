<?php

namespace Opscale\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ValidatedModel extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'description',
    ];

    public function validate(string $key): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ];
    }
}