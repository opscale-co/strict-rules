<?php

namespace Opscale\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Opscale\Services\LogicService;

class ValidController
{
    public function __construct(
        private LogicService $logicService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $request->all();
        $result = $this->logicService->processData($data);
        
        return response()->json($result);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
        ]);

        $result = $this->logicService->safeProcessData($validated);
        
        if ($result === null) {
            return response()->json(['error' => 'Processing failed'], 500);
        }

        return response()->json($result, 201);
    }
}