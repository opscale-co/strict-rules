<?php

namespace Opscale\Services;

use Illuminate\Http\Client\PendingRequest;
use Opscale\Contracts\Batchable;
use Opscale\Services\BatchingService;
use Illuminate\Support\Facades\Response;
use Opscale\Jobs\CleanOldProducts;

class ExternalAPIService
{
    public function __construct(
        int $timeout,
        $baseUrl,
        PendingRequest $request,
        Batchable $batchable)
    {
    }

    protected function canBatch(): bool
    {
        $service = new BatchingService();
        return $service->getBatchStatus('batchId') != null;
    }
}
