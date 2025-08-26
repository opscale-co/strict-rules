<?php

namespace Opscale\Models;

use Illuminate\Database\Eloquent\Model;

abstract class AbstractModel extends Model
{
    protected $fillable = ['name'];

    abstract public function getDisplayName(): string;

    public function getCreatedDate(): string
    {
        return $this->created_at->format('Y-m-d');
    }
}