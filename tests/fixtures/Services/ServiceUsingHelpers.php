<?php

namespace Opscale\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Opscale\Models\Product;

class ServiceUsingHelpers
{
    public function summarize(Collection $products): string
    {
        $names = $products->pluck('name')->all();
        $first = Arr::first($names);
        $count = Number::format($products->count());
        Log::info('summary', ['first' => $first, 'count' => $count]);

        return Str::title('summary: '.$first.' (+'.$count.')');
    }

    public function describe(Product $product): string
    {
        return $product->name;
    }
}
