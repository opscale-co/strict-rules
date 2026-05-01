<?php

namespace Opscale\Jobs;

use Opscale\Models\Product;
use Opscale\Models\Tenant;
use Opscale\Models\User;

class MultiModelJob
{
    public function handle(int $userId, Product $product): void
    {
        User::find($userId);
        $product->save();
        new Tenant(['name' => 'X']);
    }
}
