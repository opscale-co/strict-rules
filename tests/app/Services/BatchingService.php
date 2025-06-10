<?php

namespace Opscale\Services;

use Opscale\Contracts\Batchable;
use Opscale\Models\User;
use Opscale\Models\Product;

class BatchingService implements Batchable
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
}
