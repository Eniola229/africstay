<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RoomServiceOrder extends Model
{
    use HasUuids;

    protected $fillable = [
        'booking_id', 'hotel_id', 'item_id', 'quantity', 'unit_price',
        'total_price', 'status', 'requested_by', 'notes',
    ];

    protected function casts(): array
    {
        return ['unit_price' => 'integer', 'total_price' => 'integer'];
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function item()
    {
        return $this->belongsTo(RoomServiceItem::class, 'item_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function totalPriceNaira(): float
    {
        return $this->total_price / 100;
    }
}
