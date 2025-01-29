<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Esusulog extends Model
{
    use HasFactory;
    protected $fillable = [
        'esusu_id',
        'rate',
        'commence_date',
        'prev_card',
        'today_card',
        'today_cash',
        'circle_out',
        'withdrawable',
        'wallet',
        'overdraft',
        'missed_day',
        'status_id',
        'circle_out_charge',
        'txnid'
    ];
    public function esusu_type()
    {
        return $this->belongsTo(esusu_types::class,'esusu_type_id','id');
    }
    public function wb_account_details()
    {
        return $this->belongsTo(wb_account_details::class);
    }
}
