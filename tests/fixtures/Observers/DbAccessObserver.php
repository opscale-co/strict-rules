<?php

namespace Opscale\Observers;

use Illuminate\Support\Facades\DB;
use Opscale\Models\Product;

class DbAccessObserver
{
    public function created(Product $product): void
    {
        DB::table('audit_log')->insert(['model' => 'product', 'id' => $product->id]);
    }
}
