<?php

namespace Opscale\Http;

use Opscale\Models\User;

class UserController
{
    public function show(int $id): ?User
    {
        return User::find($id);
    }
}
