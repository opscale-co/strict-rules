<?php

namespace Opscale\Services;

use Illuminate\Support\Facades\DB;

class DbAccessService
{
    public function readProducts(): mixed
    {
        return DB::table('products')->get();
    }
}
