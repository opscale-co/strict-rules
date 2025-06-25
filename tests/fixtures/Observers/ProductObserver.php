<?php

namespace Opscale\Observers;

use Opscale\Models\Product;
use Illuminate\Support\Facades\Response;
use Opscale\Jobs\CleanOldProducts;

class ProductObserver
{
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        // ...
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        // ...
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        // ...
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        // ...
    }

    /**
     * Handle the Product "forceDeleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        // ...
    }
}