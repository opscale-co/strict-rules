<?php

namespace Opscale\Models\Repositories;

use Opscale\Models\OrgPolicy;

trait OrgPolicyRepository
{
    public function deactivate(OrgPolicy $policy): void
    {
        $policy->active = false;
        $policy->save();
    }
}
