<?php

namespace Opscale\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Opscale\Http\Controllers\ProductsController;
use Illuminate\Support\Facades\Http;

class CleanOldProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {
    }

    public function handle()
    {
        try {

        }
        catch (\Exception $e) {
        
        }
    }
}