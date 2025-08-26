<?php

namespace Opscale\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class LargeUser extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'first_name', 'last_name', 'phone', 'address', 'city', 'state', 'zip_code',
        'country', 'birth_date', 'gender', 'profile_picture', 'bio', 'website', 'twitter', 'facebook', 'linkedin'
    ];

    protected $hidden = [
        'password', 'remember_token', 'api_token', 'two_factor_secret', 'two_factor_recovery_codes'
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function getName()
    {
        return $this->name ?? $this->first_name . ' ' . $this->last_name;
    }

    public function getEmail()
    {
        if(isset($this->email)) {
            return $this->email;
        }
        return "example@domain.com";
    }

    public function getFullName()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getProfilePictureUrl()
    {
        return $this->profile_picture ? asset('storage/' . $this->profile_picture) : asset('images/default-avatar.png');
    }

    public function isEmailVerified()
    {
        return !is_null($this->email_verified_at);
    }

    public function hasCompletedProfile()
    {
        return !empty($this->first_name) && !empty($this->last_name) && !empty($this->phone);
    }

    public function getAgeAttribute()
    {
        return $this->birth_date ? $this->birth_date->age : null;
    }

    public function getSocialLinksAttribute()
    {
        return [
            'website' => $this->website,
            'twitter' => $this->twitter,
            'facebook' => $this->facebook,
            'linkedin' => $this->linkedin,
        ];
    }

    public function canReceiveNotifications()
    {
        return $this->isEmailVerified() && $this->notification_preferences['email'] ?? true;
    }

    public function getDisplayNameAttribute()
    {
        return $this->getFullName() ?: $this->name ?: $this->email;
    }

    public function updateLastLogin()
    {
        $this->update(['last_login_at' => now()]);
    }

    public function generateApiToken()
    {
        $this->api_token = Str::random(80);
        $this->save();
        return $this->api_token;
    }

    public function revokeApiToken()
    {
        $this->api_token = null;
        $this->save();
    }

    public function enableTwoFactorAuth()
    {
        $this->two_factor_secret = encrypt(app('pragmarx.google2fa')->generateSecretKey());
        $this->save();
    }

    public function disableTwoFactorAuth()
    {
        $this->two_factor_secret = null;
        $this->two_factor_recovery_codes = null;
        $this->two_factor_confirmed_at = null;
        $this->save();
    }

    public function confirmTwoFactorAuth()
    {
        $this->two_factor_confirmed_at = now();
        $this->save();
    }

    public function generateTwoFactorRecoveryCodes()
    {
        $this->two_factor_recovery_codes = encrypt(json_encode(collect(range(1, 8))->map(function () {
            return Str::random(10) . '-' . Str::random(10);
        })->all()));
        $this->save();
    }

    public function replaceRecoveryCode($code)
    {
        $recoveryCodes = decrypt($this->two_factor_recovery_codes);
        $recoveryCodes = json_decode($recoveryCodes, true);
        
        if (($key = array_search($code, $recoveryCodes)) !== false) {
            unset($recoveryCodes[$key]);
            $this->two_factor_recovery_codes = encrypt(json_encode($recoveryCodes));
            $this->save();
            return true;
        }
        
        return false;
    }

    public function sendWelcomeNotification()
    {
        // Send welcome email and SMS
        $this->notify(new WelcomeNotification());
    }

    public function markEmailAsVerified()
    {
        $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    public function hasVerifiedEmail()
    {
        return ! is_null($this->email_verified_at);
    }

    public function getEmailForVerification()
    {
        return $this->email;
    }

    public function getEmailForPasswordReset()
    {
        return $this->email;
    }

    public function routeNotificationForMail()
    {
        return $this->email;
    }

    public function routeNotificationForSms()
    {
        return $this->phone;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    public function scopeWithTwoFactor($query)
    {
        return $query->whereNotNull('two_factor_secret');
    }

    public function scopeRecentlyActive($query, $days = 30)
    {
        return $query->where('last_login_at', '>=', now()->subDays($days));
    }

    public function getRouteKeyName()
    {
        return 'username';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)->firstOrFail();
    }
}