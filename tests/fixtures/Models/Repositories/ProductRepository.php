<?php

namespace Opscale\Models\Repositories;

use Opscale\Models\Product;

trait ProductRepository
{
    public function disableProduct(Product $product): void
    {
        $product->active = false;
        $product->save();
    }
}
