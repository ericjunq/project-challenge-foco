<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illumante\Database\Eloquent\Factories\HasFactories;

class Reserve extends Model
{
    use HasFactory;

    protected $fillable = ['hotel_id', 'room_id', 'check_in', 'check_out', 'total'];
}
