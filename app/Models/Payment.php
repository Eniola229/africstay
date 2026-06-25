<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasUuids;

    protected $fillable = [
        'booking_id', 'hotel_id', 'amount', 'payment_method', 'provider',
        'virtual_account_number', 'virtual_account_bank', 'virtual_account_name',
        'payment_reference', 'provider_reference', 'status', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function amountNaira(): float
    {
        return $this->amount / 100;
    }
}
