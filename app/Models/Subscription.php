<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'hotel_id', 'tier', 'monthly_fee', 'started_at',
        'next_billing_date', 'is_active', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'next_billing_date' => 'datetime',
            'cancelled_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }
}