<?php

namespace Opscale\Models;

class ConcreteChildModel extends AbstractParentModel
{
    // Implements abstract method - should NOT trigger ParentCallRule
    public function getName(): string
    {
        return 'concrete name';
    }
    
    // Overrides concrete method without parent:: call - SHOULD trigger ParentCallRule
    public function getDescription(): string
    {
        return 'child description';
    }
    
    // Overrides concrete method with parent:: call - should NOT trigger ParentCallRule
    public function getType(): string
    {
        return parent::getType() . ' - child';
    }
}