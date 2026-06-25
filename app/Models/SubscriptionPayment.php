<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    use HasUuids;

    protected $fillable = [
        'hotel_id', 'subscription_id', 'tier', 'billing_cycle', 'amount',
        'provider', 'payment_reference', 'provider_reference', 'status', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
        ];
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
