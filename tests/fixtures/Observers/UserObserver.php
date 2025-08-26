<?php

namespace Opscale\Observers;

use Exception;
use RuntimeException;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    public function created(User $user): void
    {
        try {
            $this->sendWelcomeEmail($user);
        } catch (Exception $e) {
            Log::error('Failed to send welcome email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function sendWelcomeEmail(User $user): void
    {
        // Email sending logic
    }
}