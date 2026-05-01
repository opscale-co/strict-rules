<?php

namespace Opscale\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Laravel\Nova\Resource;
use Laravel\Sanctum\Sanctum;
use Mcp\Server\ServerInterface;
use PhpMcp\Server\Tool;

class McpInertiaController
{
    public function show(Request $request, ServerInterface $mcp, Tool $tool): mixed
    {
        Log::info('mcp request', ['user' => Auth::id()]);
        Sanctum::ignoreMigrations();

        return Inertia::render('Dashboard', [
            'tool' => $tool,
            'resource' => Resource::class,
        ]);
    }
}
