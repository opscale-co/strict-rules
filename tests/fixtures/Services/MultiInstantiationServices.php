<?php

namespace Opscale\Services;

class FirstCleanService
{
    public function __construct(private readonly BatchingService $batching) {}

    public function process(): void
    {
        $this->batching->processBatch('id', []);
    }
}

class SecondViolatingService
{
    public function process(): void
    {
        $batching = new BatchingService();
        $batching->processBatch('id', []);
    }
}
