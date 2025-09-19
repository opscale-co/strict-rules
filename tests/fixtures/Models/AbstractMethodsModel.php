<?php

namespace Opscale\Models;

use Illuminate\Database\Eloquent\Model;

abstract class AbstractMethodsModel extends Model
{
    // Abstract method - should NOT trigger ConditionalOverrideRule
    abstract public function getName(): string;
    
    // Abstract protected method - should NOT trigger ConditionalOverrideRule
    abstract protected function getInternalName(): string;
    
    // Concrete method without final or Override - SHOULD trigger ConditionalOverrideRule
    public function getDescription(): string
    {
        return 'description';
    }
    
    // Final method - should NOT trigger ConditionalOverrideRule
    final public function getType(): string
    {
        return 'type';
    }
    
    // Method with Override - should NOT trigger ConditionalOverrideRule
    #[\Override]
    public function save(array $options = []): bool
    {
        return true;
    }
    
    // Private method - should NOT trigger ConditionalOverrideRule (not public/protected)
    private function getPrivateData(): string
    {
        return 'private';
    }
}