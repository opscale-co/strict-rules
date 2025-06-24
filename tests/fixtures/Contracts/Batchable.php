<?php

namespace Opscale\Contracts;

interface Batchable
{
    public function processBatch(string $batchId, array $items): array;
    public function getBatchStatus(string $batchId): string;
    public function completeBatch(string $batchId): void;
}
