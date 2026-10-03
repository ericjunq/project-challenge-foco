<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illumante\Database\Eloquent\Factories\HasFactories;

class Reserve extends Model
{
    use HasFactory;

    protected $fillable = ['hotel_id', 'room_id', 'check_in', 'check_out', 'total'];

    public function hotel(): BelongsTo {
        return $this->belongsTo(Hotel::class);
    }

    public function room(): BelongsTo {
        return $this->belongsTo(Room::class);
    }

    public function guests(): HasMany {
        return $this->hasMany(Guest::class);
    }

    public function dailies(): HasMany {
        return $this->hasMany(Daily::class);
    }

    public function payments() : HasMany{
        return $this->hasMany(Payment::class);
    }

    protected function casts(): array{

        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'total' => 'decimal:2',
        ];
    }

}
