<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'hotel_id', 'guest_id', 'type', 'recipient', 'message', 'provider_reference',
        'status', 'was_fallback', 'event', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'was_fallback' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }
}
