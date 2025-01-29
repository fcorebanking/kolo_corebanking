<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingEsusu extends Model
{
    use HasFactory;
  
 
    protected $fillable = [
        'esusuid',
     
        'amount',
        'number',
        'wallet_to_main',
        'withdraw_to_wallet',
        'reset_rate',
        'reset_date',
        'status_id',
        'pending',
        'created_by'
    ];
    public function esusu()
    {
        return $this->belongsTo(Esusu::class,'id','esusuid');
    }
    public function author()
    {
        return $this->belongsTo(User::class,'id','created_by');
    }
}
