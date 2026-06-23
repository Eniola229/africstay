<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'platform_admin_id', 'role', 'action', 'action_category',
        'target_type', 'target_id', 'target_label', 'description',
        'old_value', 'new_value', 'ip_address', 'user_agent', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_value' => 'array',
            'new_value' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public static function record(
        ?PlatformAdmin $admin,
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
            'platform_admin_id' => $admin?->id,
            'role' => $admin?->role,
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
            'created_at' => now(),
        ]);
    }

    public function admin()
    {
        return $this->belongsTo(PlatformAdmin::class, 'platform_admin_id');
    }
}