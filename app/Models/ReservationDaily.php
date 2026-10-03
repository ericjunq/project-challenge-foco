<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationDaily extends Model
{
    protected $fillable = ['reserve_id', 'date', 'value'];
}
