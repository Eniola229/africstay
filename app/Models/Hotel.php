<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    use HasUuids;

    protected $fillable = [
        'name', 'slug', 'address', 'city', 'state', 'country', 'phone', 'email',
        'logo', 'description', 'tier', 'wallet_balance', 'owner_id', 'is_active',
        'onboarding_step', 'onboarding_completed', 'subscription_status', 'subscription_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'onboarding_completed' => 'boolean',
            'wallet_balance' => 'integer',
            'subscription_ends_at' => 'datetime',
        ];
    }

    public const TIER_ROOM_LIMITS = [
        'starter' => 15,
        'growth' => 50,
        'pro' => null,
        'enterprise' => null,
    ];

    public const TIER_STAFF_LIMITS = [
        'starter' => 2,
        'growth' => 10,
        'pro' => null,
        'enterprise' => null,
    ];

    // Base MONTHLY fee in kobo. Yearly = monthly * 12 * 0.8 (20% off), see Subscription::amountFor().
    public const TIER_MONTHLY_FEES = [
        'starter' => 2000000,   // ₦20,000
        'growth' => 5000000,    // ₦50,000
        'pro' => 8000000,       // ₦80,000
        'enterprise' => null,   // contact sales — no self-serve price
    ];

    public const YEARLY_DISCOUNT_PERCENT = 20;

    public const GRACE_PERIOD_DAYS = 3; // days after expiry before hard lockout (status -> past_due, not expired)

    // AfricStay's cut of every confirmed guest payment, deducted before crediting the wallet.
    public const TIER_TRANSACTION_FEE_PERCENT = [
        'starter' => 1.5,
        'growth' => 1.0,
        'pro' => 0.75,
        'enterprise' => 0.5,
    ];

    public const MIN_WITHDRAWAL_KOBO = 1000000; // ₦10,000 minimum, per spec

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function guests()
    {
        return $this->hasMany(Guest::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->where('is_active', true);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function walletBalanceNaira(): float
    {
        return $this->wallet_balance / 100;
    }

    /** Credits the wallet after deducting AfricStay's tier-based transaction fee. Returns the fee charged (kobo). */
    public function creditWalletAfterFee(int $grossAmountKobo): int
    {
        $feePercent = self::TIER_TRANSACTION_FEE_PERCENT[$this->tier] ?? 1.5;
        $fee = (int) round($grossAmountKobo * ($feePercent / 100));
        $net = $grossAmountKobo - $fee;

        $this->increment('wallet_balance', $net);

        return $fee;
    }

    public function debitWallet(int $amountKobo): bool
    {
        if ($this->wallet_balance < $amountKobo) {
            return false;
        }

        $this->decrement('wallet_balance', $amountKobo);
        return true;
    }

    /** Has the hotel ever completed a paid subscription, or is it still gated at registration? */
    public function hasEverPaid(): bool
    {
        return $this->subscriptions()->where('status', '!=', 'pending')->exists();
    }

    public function hasUsableAccess(): bool
    {
        return in_array($this->subscription_status, ['active', 'past_due']);
    }
}
