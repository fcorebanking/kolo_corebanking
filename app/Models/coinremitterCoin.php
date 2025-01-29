<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class coinremitterCoin extends Model
{
    use HasFactory;
    public function status()
    {
        return $this->belongsTo(status::class, 'status_id', 'id');
    }
}
