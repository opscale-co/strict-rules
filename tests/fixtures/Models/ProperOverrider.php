<?php

namespace Opscale\Models;

class ProperOverrider extends AbstractParentModel
{
    public function getName(): string
    {
        return 'implemented';
    }

    public function getDescription(): string
    {
        return parent::getDescription().' (extended)';
    }

    public function getType(): string
    {
        return parent::getType();
    }
}
