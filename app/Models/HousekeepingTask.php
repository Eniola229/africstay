<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class HousekeepingTask extends Model
{
    use HasUuids;

    protected $fillable = [
        'room_id', 'hotel_id', 'assigned_to', 'triggered_by', 'status',
        'checklist', 'completed_at', 'verified_by', 'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'checklist' => 'array',
            'completed_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /** Default checklist by room type — configurable later via hotel settings if needed. */
    public static function defaultChecklistFor(string $roomType): array
    {
        $base = [
            ['label' => 'Strip and remake bed', 'done' => false],
            ['label' => 'Clean bathroom', 'done' => false],
            ['label' => 'Vacuum/mop floor', 'done' => false],
            ['label' => 'Restock toiletries', 'done' => false],
            ['label' => 'Empty trash', 'done' => false],
        ];

        if (in_array($roomType, ['suite', 'family'])) {
            $base[] = ['label' => 'Clean living/sitting area', 'done' => false];
        }

        return $base;
    }
}
