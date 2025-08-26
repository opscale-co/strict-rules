<?php

namespace Opscale\Services;

use Illuminate\Http\Client\PendingRequest;
use Opscale\Contracts\Batchable;
use Opscale\Services\BatchingService;

class ValidDependencyInjection
{
    public function __construct(
        private BatchingService $batchingService,
        private PendingRequest $request,
        private Batchable $batchable
    ) {}

    public function canBatch(): bool
    {
        return $this->batchingService->getBatchStatus('batchId') !== null;
    }

    public function processWithMethodInjection(BatchingService $service): bool
    {
        return $service->getBatchStatus('batchId') !== null;
    }

    public function processWithFactoryPattern(): bool
    {
        $service = app(BatchingService::class);
        return $service->getBatchStatus('batchId') !== null;
    }
}