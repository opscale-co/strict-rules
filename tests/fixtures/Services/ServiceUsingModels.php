<?php

namespace Opscale\Services;

use Opscale\Models\User;
use Opscale\Models\Product;

class ServiceUsingModels
{
    public function processUserData(User $user): array
    {
        return [
            'user' => $user->toArray(),
            'products' => Product::where('user_id', $user->id)->get()
        ];
    }
}