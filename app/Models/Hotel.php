<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    protected $fillable = [
        'name', 'slug', 'address', 'city', 'state', 'country', 'phone', 'email',
        'logo', 'description', 'tier', 'wallet_balance', 'owner_id', 'is_active',
        'onboarding_step', 'onboarding_completed',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'onboarding_completed' => 'boolean',
            'wallet_balance' => 'integer',
        ];
    }

    // Tier room limits — enforced in controllers, not just UI (see spec note #8)
    public const TIER_ROOM_LIMITS = [
        'starter' => 15,
        'growth' => 50,
        'pro' => null,       // unlimited
        'enterprise' => null,
    ];

    public const TIER_STAFF_LIMITS = [
        'starter' => 2,
        'growth' => 10,
        'pro' => null,
        'enterprise' => null,
    ];

    public const TIER_FEES = [ // monthly fee in kobo
        'starter' => 2000000,
        'growth' => 5000000,
        'pro' => 8000000,
        'enterprise' => null, // contact sales
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->where('is_active', true)->latestOfMany();
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function walletBalanceNaira(): float
    {
        return $this->wallet_balance / 100;
    }
}