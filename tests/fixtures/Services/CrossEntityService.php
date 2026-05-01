<?php

namespace Opscale\Services;

use Opscale\Models\OrgPolicy;
use Opscale\Models\Product;
use Opscale\Models\Tenant;
use Opscale\Models\User;

class CrossEntityService
{
    public function orchestrate(): void
    {
        User::find(1);
        Product::find(1);
        Tenant::find(1);
        OrgPolicy::find(1);
    }
}
