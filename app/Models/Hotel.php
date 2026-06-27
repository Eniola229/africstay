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
        'online_booking_enabled', 'online_booking_deposit_percent', 'meta_title', 'meta_description',
        'parent_hotel_id', 'brand_primary_color',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'onboarding_completed' => 'boolean',
            'wallet_balance' => 'integer',
            'subscription_ends_at' => 'datetime',
            'online_booking_enabled' => 'boolean',
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

    public const MAX_LOCATIONS_PRO = 3; // Pro tier: "up to 3 locations" per spec

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

    public function housekeepingTasks()
    {
        return $this->hasMany(HousekeepingTask::class);
    }

    public function roomServiceItems()
    {
        return $this->hasMany(RoomServiceItem::class);
    }

    public function roomServiceOrders()
    {
        return $this->hasMany(RoomServiceOrder::class);
    }

    /** The primary hotel this is a child location of, or null if this IS the primary. */
    public function parentHotel()
    {
        return $this->belongsTo(Hotel::class, 'parent_hotel_id');
    }

    /** Child locations under this hotel (only meaningful if this is a primary/standalone hotel). */
    public function childLocations()
    {
        return $this->hasMany(Hotel::class, 'parent_hotel_id');
    }

    public function isPrimaryLocation(): bool
    {
        return $this->parent_hotel_id === null;
    }

    /** This hotel + all its child locations (or just itself if it's a child / has none) — for multi-location aggregation. */
    public function allLocationIds(): array
    {
        if (! $this->isPrimaryLocation()) {
            return [$this->id];
        }

        return $this->childLocations()->pluck('id')->push($this->id)->all();
    }

    public function totalLocationCount(): int
    {
        return $this->isPrimaryLocation() ? $this->childLocations()->count() + 1 : 1;
    }

    public function canAddLocation(): bool
    {
        return $this->tier === 'pro' && $this->isPrimaryLocation() && $this->totalLocationCount() < self::MAX_LOCATIONS_PRO;
    }

    public function staffLimit(): ?int
    {
        return self::TIER_STAFF_LIMITS[$this->tier] ?? null;
    }

    public function staffCount(): int
    {
        return $this->users()->where('role', '!=', 'owner')->count();
    }

    public function canInviteMoreStaff(): bool
    {
        $limit = $this->staffLimit();
        return $limit === null || $this->staffCount() < $limit;
    }

    public function isApproachingStaffLimit(): bool
    {
        $limit = $this->staffLimit();
        return $limit !== null && $this->staffCount() >= (int) floor($limit * 0.8);
    }

    public function isApproachingRoomLimit(): bool
    {
        $limit = self::TIER_ROOM_LIMITS[$this->tier] ?? null;
        return $limit !== null && $this->rooms()->count() >= (int) floor($limit * 0.8);
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
