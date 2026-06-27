<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Hotel-side user: owner or staff. Scoped to a single hotel via hotel_id.
 * Never used for platform admins — see PlatformAdmin model + `platform` guard.
 */
class User extends Authenticatable
{
    use HasApiTokens, HasUuids, Notifiable;

    protected $fillable = [
        'hotel_id', 'name', 'email', 'phone', 'password', 'role',
        'is_active', 'last_login_at', 'invite_token', 'invite_expires_at',
        'must_set_password',
    ];

    protected $hidden = ['password', 'remember_token', 'invite_token'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'must_set_password' => 'boolean',
            'last_login_at' => 'datetime',
            'invite_expires_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function canManageHotel(): bool
    {
        return in_array($this->role, ['owner', 'manager']);
    }

    public function canWithdrawFunds(): bool
    {
        return $this->role === 'owner';
    }
}
