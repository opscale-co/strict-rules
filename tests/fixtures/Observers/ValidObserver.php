<?php

namespace Opscale\Observers;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Opscale\Models\Product;

class ValidObserver implements ShouldBroadcast
{
    public function created(Product $product): void
    {
        Log::info('product.created', ['id' => $product->id]);
        Event::dispatch('product.created', [$product]);
        Broadcast::on('product');
    }

    public function describe(Model $model): string
    {
        return 'observed: '.$model::class;
    }
}
