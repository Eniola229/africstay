<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'hotel_id', 'room_id', 'guest_id', 'receptionist_id', 'booking_reference',
        'check_in', 'check_out', 'nights', 'pricing_unit',
        'total_amount', 'amount_paid', 'balance',
        'status', 'booking_source', 'notes',
        'checked_in_at', 'checked_out_at', 'cancelled_at',
        'checkout_alert_sent',
    ];

    protected function casts(): array
    {
        return [
            // Full datetimes — supports hour/24h bookings
            'check_in'             => 'datetime',
            'check_out'            => 'datetime',
            'checked_in_at'        => 'datetime',
            'checked_out_at'       => 'datetime',
            'cancelled_at'         => 'datetime',
            'total_amount'         => 'integer',
            'amount_paid'          => 'integer',
            'balance'              => 'integer',
            'checkout_alert_sent'  => 'boolean',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

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

    // ── Business logic ────────────────────────────────────────────────────────

    /**
     * Double-booking guard.
     * Returns true if any active booking for this room overlaps the given window.
     */
    public static function hasOverlap(
        string  $roomId,
        string  $checkIn,
        string  $checkOut,
        ?string $excludingBookingId = null
    ): bool {
        $query = self::where('room_id', $roomId)
            ->whereNotIn('status', ['cancelled', 'checked_out'])
            ->where('check_in', '<', $checkOut)
            ->where('check_out', '>', $checkIn);

        if ($excludingBookingId) {
            $query->where('id', '!=', $excludingBookingId);
        }

        return $query->exists();
    }

    /**
     * Human-readable label for the pricing unit.
     */
    public function pricingUnitLabel(): string
    {
        return match($this->pricing_unit) {
            'hour'  => 'hour(s)',
            'day24' => '24-hour block(s)',
            default => 'night(s)',
        };
    }

    // ── Money helpers (all amounts stored in kobo) ────────────────────────────

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

    public function paymentAccount(): HasOne
    {
        return $this->hasOne(Payment::class)->latest();
    }
}