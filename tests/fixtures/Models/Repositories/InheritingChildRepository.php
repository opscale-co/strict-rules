<?php

namespace Opscale\Models\Repositories;

use Opscale\Models\InheritingChildEntity;

trait InheritingChildRepository
{
    public function rename(InheritingChildEntity $child, string $name): void
    {
        $child->name = $name;
        $child->save();
    }
}
