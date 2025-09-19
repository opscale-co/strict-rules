<?php

namespace Opscale\Classes;

class ClassWithCustomHelpers
{
    public function someMethod()
    {
        $result = request()->all();
        $user = session()->get('user');
        $url = url('/home');
        
        return $result;
    }
}