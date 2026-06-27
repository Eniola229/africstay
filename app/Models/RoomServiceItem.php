<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RoomServiceItem extends Model
{
    use HasUuids;

    protected $fillable = ['hotel_id', 'name', 'category', 'price', 'is_active'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'is_active' => 'boolean'];
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function priceNaira(): float
    {
        return $this->price / 100;
    }
}
