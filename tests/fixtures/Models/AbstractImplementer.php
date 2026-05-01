<?php

namespace Opscale\Models;

class AbstractImplementer extends AbstractParentModel
{
    public function getName(): string
    {
        return 'implemented';
    }
}
