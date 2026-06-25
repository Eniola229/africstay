<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RoomMedia extends Model
{
    use HasUuids;

    protected $fillable = [
        'room_id', 'type', 'url', 'cloudinary_public_id', 'thumbnail_url', 'is_primary', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
