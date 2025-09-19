<?php

namespace Opscale\Models;

use Illuminate\Database\Eloquent\Model;

class StaticMethodsModel extends Model
{
    // Static method that overrides parent - should NOT trigger ParentCallRule
    public static function create(array $attributes = []): static
    {
        // No parent:: call, but should be ignored since it's static
        return new static($attributes);
    }
    
    // Instance method that overrides parent - SHOULD trigger ParentCallRule
    public function save(array $options = []): bool
    {
        // No parent:: call - this should trigger the rule
        return true;
    }
    
    // Instance method that overrides parent with parent call - should NOT trigger
    public function update(array $attributes = [], array $options = []): bool
    {
        // Has parent:: call - this should not trigger the rule
        return parent::update($attributes, $options);
    }
}