<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'hotel_id', 'room_number', 'name', 'type', 'floor', 'price_per_night',
        'status', 'description', 'max_guests', 'maintenance_reason', 'maintenance_expected_return',
    ];

    protected function casts(): array
    {
        return [
            'price_per_night' => 'integer',
            'maintenance_expected_return' => 'date',
        ];
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function media()
    {
        return $this->hasMany(RoomMedia::class)->orderBy('sort_order');
    }

    public function housekeepingTasks()
    {
        return $this->hasMany(HousekeepingTask::class);
    }

    public function images()
    {
        return $this->media()->where('type', 'image');
    }

    public function videos()
    {
        return $this->media()->where('type', 'video');
    }

    public function primaryImage()
    {
        return $this->media()->where('type', 'image')->where('is_primary', true);
    }

    public function pricePerNightNaira(): float
    {
        return $this->price_per_night / 100;
    }
}
