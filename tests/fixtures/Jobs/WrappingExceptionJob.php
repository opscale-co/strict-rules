<?php

namespace Opscale\Jobs;

use RuntimeException;

class WrappingExceptionJob
{
    public function handle(): void
    {
        try {
            $this->risky();
        } catch (\Exception $e) {
            throw new RuntimeException('wrapped: '.$e->getMessage(), 0, $e);
        }
    }

    private function risky(): void {}
}
