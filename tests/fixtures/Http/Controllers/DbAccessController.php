<?php

namespace Opscale\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DbAccessController
{
    public function index(Request $request): mixed
    {
        return DB::table('products')->get();
    }
}
