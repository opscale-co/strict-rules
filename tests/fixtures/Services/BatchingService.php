<?php

namespace Opscale\Services;

use Opscale\Contracts\Batchable;
use Opscale\Models\User;
use Opscale\Models\Product;
use Opscale\Services\ExternalAPIService;

class BatchingService extends ExternalAPIService implements Batchable
{
    public function processBatch(string $batchId, array $data): array
    {
        return [];
    }

    public function getBatchStatus(string $batchId): string
    {
        throw new \Exception("Batch status not implemented");
    }

    public function completeBatch(string $batchId): void
    {
    }

    #[\Override]
    protected function canBatch(): bool
    {
        return false;
    }
}
