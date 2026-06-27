<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EnterpriseInquiry extends Model
{
    use HasUuids;

    protected $fillable = [
        'contact_name', 'hotel_name', 'email', 'phone', 'message',
        'status', 'assigned_to', 'internal_notes',
    ];

    public function assignee()
    {
        return $this->belongsTo(PlatformAdmin::class, 'assigned_to');
    }
}
