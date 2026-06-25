<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    use HasUuids;

    protected $fillable = [
        'hotel_id', 'amount', 'bank_name', 'bank_code', 'account_number', 'account_name',
        'reference', 'provider', 'provider_reference', 'status', 'initiated_by',
        'failure_reason', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function amountNaira(): float
    {
        return $this->amount / 100;
    }
}
