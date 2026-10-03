<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illumante\Database\Eloquent\Factories\HasFactories;

class Hotel extends Model
{
    use HasFactory;

    protected $fillable = ['hotel_id','name'];

    public function rooms(): HasMany{
        return $this->hasMany(Room::class);
    }

    public function reserves(): HasMany{
        return $this->hasMany(Reserve::class);
    }
}
