<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['reserve_id', 'method', 'value'];

    public function reserve(): BelongsTo
    {
        return $this->belongsTo(Reserve::class);
    }
}
