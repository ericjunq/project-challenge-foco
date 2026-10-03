<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illumante\Database\Eloquent\Factories\HasFactories;

class Room extends Model
{
    use HasFactory;

    protected $fillable = ['hotel_id', 'name'];

    public function hotel(): BelongsTo {
        return $this->belongsTo(Hotel::class);
    }

    public function reserves(): HasMany {
        return $this->hasMany(Reserve::class);
    }
}
