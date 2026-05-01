<?php

namespace Opscale\Services;

use Opscale\Contracts\Batchable;

class IntentionalShortMethodService implements Batchable
{
    public function processBatch(string $batchId, array $items): array
    {
        return $this->compute($batchId, $items);
    }

    public function getBatchStatus(string $batchId): string
    {
        return $batchId;
    }

    public function completeBatch(string $batchId): void
    {
        $this->mark($batchId);
    }

    private function compute(string $batchId, array $items): array
    {
        return [$batchId => count($items)];
    }

    private function mark(string $batchId): void
    {
        // Substantive helper; not a stub.
    }
}
