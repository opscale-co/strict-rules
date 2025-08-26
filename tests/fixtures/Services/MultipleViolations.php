<?php

namespace Opscale\Services;

use Opscale\Services\BatchingService;
use Opscale\Models\User;
use Illuminate\Support\Facades\Cache;

class MultipleViolations
{
    public function processData(): array
    {
        $batchService = new BatchingService();
        $user = new User();
        
        $results = [];
        
        if ($batchService->canProcess()) {
            $results[] = $user->getName();
        }

        return $results;
    }

    public function anotherMethod(): bool
    {
        $service = new BatchingService();
        return $service->getBatchStatus('test') !== null;
    }

    public function createUserInstance(): User
    {
        return new User(['name' => 'Test']);
    }
}