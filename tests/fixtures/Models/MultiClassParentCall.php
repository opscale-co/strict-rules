<?php

namespace Opscale\Models;

class FirstProperOverrider extends AbstractParentModel
{
    public function getName(): string
    {
        return 'first';
    }

    public function getDescription(): string
    {
        return parent::getDescription();
    }
}

class SecondImproperOverrider extends AbstractParentModel
{
    public function getName(): string
    {
        return 'second';
    }

    public function getDescription(): string
    {
        return 'replaced without parent::';
    }
}
