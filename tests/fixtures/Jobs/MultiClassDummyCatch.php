<?php

namespace Opscale\Jobs;

use Illuminate\Support\Facades\Log;

class FirstHandledJob
{
    public function handle(): void
    {
        try {
            $this->risky();
        } catch (\Exception $e) {
            Log::error('first failed', ['exception' => $e->getMessage()]);
        }
    }

    private function risky(): void {}
}

class SecondDummyJob
{
    public function handle(): void
    {
        try {
            $this->risky();
        } catch (\Exception $e) {
        }
    }

    private function risky(): void {}
}
