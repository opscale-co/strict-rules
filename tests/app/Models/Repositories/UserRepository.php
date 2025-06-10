<?php

namespace Opscale\Models\Repositories;

use Opscale\Models\User;
use Opscale\Models\Product;

trait UserRepository
{
    public static function firstAdmin()
    {
        return self::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->first();
    }   

    public function isFromCompany()
    {
        return $this->where('email', 'like', '%@example.com')->exists();
    }
}
