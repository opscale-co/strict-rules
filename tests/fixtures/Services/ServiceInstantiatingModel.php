<?php

namespace Opscale\Services;

use Opscale\Mail\SendOrderEmail;
use Opscale\Models\Product;
use Opscale\Models\User;

class ServiceInstantiatingModel
{
    public function createOrder(array $payload): User
    {
        $user = new User($payload);
        $product = new Product(['name' => 'sample']);
        $mail = new SendOrderEmail($payload);

        return $user;
    }
}
