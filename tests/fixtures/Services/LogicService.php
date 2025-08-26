<?php

namespace Opscale\Services;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class LogicService
{
    public function processData(array $data): array
    {
        try {
            $this->validateData($data);
            return $this->transformData($data);
        }
        catch (InvalidArgumentException $e) {
            Log::warning('Invalid data provided: ' . $e->getMessage());
            throw $e;
        }
        catch (RuntimeException $e) {
            Log::error('Processing failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function safeProcessData(array $data): ?array
    {
        try {
            return $this->transformData($data);
        }
        catch (\Exception $e) {
            Log::error('Safe processing failed: ' . $e->getMessage());
            return null;
        }
    }

    private function validateData(array $data): void
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Data cannot be empty');
        }
    }

    private function transformData(array $data): array
    {
        return array_map('strtoupper', $data);
    }
}