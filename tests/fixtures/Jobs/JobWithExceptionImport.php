<?php

namespace Opscale\Jobs;

use Exception;
use InvalidArgumentException;

class JobWithExceptionImport
{
    public function handle(): void
    {
        $data = $this->getData();
        
        if (empty($data)) {
            throw new InvalidArgumentException('Data cannot be empty');
        }
        
        $this->processData($data);
    }

    private function getData(): array
    {
        return [];
    }

    private function processData(array $data): void
    {
        // Processing logic
    }
}