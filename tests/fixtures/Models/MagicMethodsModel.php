<?php

namespace Opscale\Models;

use Illuminate\Database\Eloquent\Model;

class MagicMethodsModel extends Model
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    public function __toString(): string
    {
        return 'magic';
    }

    public function __invoke(): string
    {
        return 'invoke';
    }
}
