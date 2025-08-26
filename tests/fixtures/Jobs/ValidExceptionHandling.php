<?php

namespace Opscale\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ValidExceptionHandling implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() 
    {
    }

    public function handle()
    {
        try {
            $this->processData();
        }
        catch (\Exception $e) {
            Log::error('Failed to process data: ' . $e->getMessage());
            throw $e;
        }
    }

    public function handleWithSpecificException()
    {
        try {
            $this->processData();
        }
        catch (\InvalidArgumentException $e) {
            Log::warning('Invalid argument provided: ' . $e->getMessage());
            return false;
        }
        catch (\Exception $e) {
            Log::error('Unexpected error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function processData()
    {
        // Some processing logic
    }
}