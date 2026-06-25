<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasUuids;

    protected $fillable = [
        'hotel_id', 'tier', 'billing_cycle', 'base_monthly_fee', 'discount_percent',
        'amount_due', 'status', 'starts_at', 'ends_at', 'is_active', 'cancelled_at',
        'renewal_reminder_7d_sent', 'renewal_reminder_3d_sent', 'renewal_reminder_1d_sent',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'is_active' => 'boolean',
            'renewal_reminder_7d_sent' => 'boolean',
            'renewal_reminder_3d_sent' => 'boolean',
            'renewal_reminder_1d_sent' => 'boolean',
        ];
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function payments()
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    /**
     * Amount owed in kobo for a given tier + billing cycle.
     * Yearly = monthly * 12, minus Hotel::YEARLY_DISCOUNT_PERCENT.
     */
    public static function amountFor(string $tier, string $billingCycle): ?int
    {
        $monthly = Hotel::TIER_MONTHLY_FEES[$tier] ?? null;

        if ($monthly === null) {
            return null; // enterprise — sales-assisted, no self-serve amount
        }

        if ($billingCycle === 'yearly') {
            $yearly = $monthly * 12;
            return (int) round($yearly * (1 - Hotel::YEARLY_DISCOUNT_PERCENT / 100));
        }

        return $monthly;
    }

    public function isExpired(): bool
    {
        return $this->ends_at !== null && $this->ends_at->isPast();
    }
}
