<?php

namespace Opscale\Models\Repositories;

use Opscale\Models\Tenant;

trait TenantRepository
{
    public function deactivate(Tenant $tenant): void
    {
        $tenant->active = false;
        $tenant->save();
    }
}
