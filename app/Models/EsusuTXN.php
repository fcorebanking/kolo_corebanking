<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EsusuTXN extends Model
{
    use HasFactory;
    protected $fillable =  ['esusu_id','no_card','transaction_id','created_date'];
    public function esusu_txn()
    {
        return $this->belongsTo(Esusu::class,'id','esusu_id');
    }
    public function transaction()
    {
        return $this->belongsTo(transactions::class,'id','transaction_id');
    }
    
}
