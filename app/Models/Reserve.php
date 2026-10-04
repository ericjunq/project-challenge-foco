<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reserve extends Model
{
    use HasFactory;

    protected $fillable = ['hotel_id', 'room_id', 'check_in', 'check_out', 'total', 'external_id'];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    public function dailies(): HasMany
    {
        return $this->hasMany(Daily::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    protected function casts(): array
    {

        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'total' => 'decimal:2',
        ];
    }

}
