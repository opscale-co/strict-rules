<?php

namespace Opscale\Classes;

class ClassWithStaticMethods
{
    public function someMethod()
    {
        // These should be allowed as they are static method calls, not helper functions
        $result = MyUtility::formatData($data);
        $value = SomeClass::CONSTANT;
        $processed = DataProcessor::process($input);
        
        // Facades should also be allowed now
        $user = Auth::user();
        $data = Cache::get('key');
        
        return $result;
    }
}