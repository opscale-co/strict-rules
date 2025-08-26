<?php

namespace Opscale\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class ValidService
{
    public function processData(): array
    {
        $response = Http::get('https://api.example.com/data');
        
        return [
            'data' => $response->json(),
            'processed_at' => Carbon::now(),
        ];
    }
}