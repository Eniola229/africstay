<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guest extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'hotel_id', 'name', 'phone', 'email', 'id_type', 'id_number',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function hasContactInfo(): bool
    {
        return filled($this->phone) || filled($this->email);
    }
}
