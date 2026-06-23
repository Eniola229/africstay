<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false; // created_at only, set manually

    protected $fillable = [
        'hotel_id', 'user_id', 'user_name', 'user_role', 'action', 'action_category',
        'target_type', 'target_id', 'target_label', 'description',
        'old_value', 'new_value', 'ip_address', 'user_agent', 'session_id', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_value' => 'array',
            'new_value' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Convenience logger — call from controllers:
     * ActivityLog::record($hotelId, $user, 'LOGIN', 'auth', null, null, null, 'Owner Chidi Obi logged in.');
     */
    public static function record(
        int $hotelId,
        ?User $user,
        string $action,
        string $category,
        ?string $targetType,
        ?int $targetId,
        ?string $targetLabel,
        string $description,
        array $old = [],
        array $new = []
    ): self {
        return self::create([
            'hotel_id' => $hotelId,
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'user_role' => $user?->role,
            'action' => $action,
            'action_category' => $category,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'target_label' => $targetLabel,
            'description' => $description,
            'old_value' => $old ?: null,
            'new_value' => $new ?: null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'created_at' => now(),
        ]);
    }
}