<?php

namespace Opscale\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MultipleDummyCatches implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        try {
            $this->processFirstStep();
        }
        catch (\Exception $e) {
        }

        try {
            $this->processSecondStep();
        }
        catch (\InvalidArgumentException $e) {
        }
        catch (\RuntimeException $e) {
        }
    }

    private function processFirstStep()
    {
        // Some logic
    }

    private function processSecondStep()
    {
        // Some other logic
    }
}