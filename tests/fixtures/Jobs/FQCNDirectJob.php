<?php

namespace Opscale\Jobs;

class FQCNDirectJob
{
    public function handle(): void
    {
        \Opscale\Models\User::find(1);
        \Opscale\Models\Product::find(1);
        \Opscale\Models\Tenant::find(1);
    }
}
