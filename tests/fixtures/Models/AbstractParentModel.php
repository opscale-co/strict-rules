<?php

namespace Opscale\Models;

use Illuminate\Database\Eloquent\Model;

abstract class AbstractParentModel extends Model
{
    // Abstract method - implementations should NOT trigger ParentCallRule
    abstract public function getName(): string;
    
    // Concrete method - overrides should trigger ParentCallRule if no parent:: call
    public function getDescription(): string
    {
        return 'base description';
    }
    
    // Another concrete method
    public function getType(): string
    {
        return 'base type';
    }
}