<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'hotel_id', 'room_id', 'guest_id', 'receptionist_id', 'booking_reference',
        'check_in', 'check_out', 'nights', 'total_amount', 'amount_paid', 'balance',
        'status', 'booking_source', 'notes', 'checked_in_at', 'checked_out_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'total_amount' => 'integer',
            'amount_paid' => 'integer',
            'balance' => 'integer',
        ];
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function receptionist()
    {
        return $this->belongsTo(User::class, 'receptionist_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function roomServiceOrders()
    {
        return $this->hasMany(RoomServiceOrder::class);
    }

    /**
     * The core double-booking guard: does any OTHER active booking for this
     * room overlap these dates? Active = not cancelled, not already checked out.
     */
    public static function hasOverlap(string $roomId, string $checkIn, string $checkOut, ?string $excludingBookingId = null): bool
    {
        $query = self::where('room_id', $roomId)
            ->whereNotIn('status', ['cancelled', 'checked_out'])
            ->where('check_in', '<', $checkOut)
            ->where('check_out', '>', $checkIn);

        if ($excludingBookingId) {
            $query->where('id', '!=', $excludingBookingId);
        }

        return $query->exists();
    }

    public function totalAmountNaira(): float
    {
        return $this->total_amount / 100;
    }

    public function amountPaidNaira(): float
    {
        return $this->amount_paid / 100;
    }

    public function balanceNaira(): float
    {
        return $this->balance / 100;
    }
}
