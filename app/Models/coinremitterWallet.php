<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class coinremitterWallet extends Model
{
    use HasFactory;
    public function coin()
    {
        return $this->belongsTo(coinremitterCoin::class,'coinremitter_coin_id','id');
    }
}
