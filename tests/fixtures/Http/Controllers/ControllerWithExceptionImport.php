<?php

namespace Opscale\Http\Controllers;

use RuntimeException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ControllerWithExceptionImport
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->all();
        
        if (empty($data['name'])) {
            throw new RuntimeException('Name is required');
        }

        return response()->json(['success' => true]);
    }
}