<?php

namespace Opscale\Classes;

class ClassWithHelpers
{
    public function someMethod()
    {
        $user = auth()->user();
        $data = cache()->get('key');
        $config = config('app.name');
        
        return $data;
    }
}