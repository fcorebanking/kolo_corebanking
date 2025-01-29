<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Esusu extends Model
{
    use HasFactory;
    protected $fillable = [
        'wb_account_detail_id',
        'rate',
        'esusu_type_id',
        'commence_date',
        'start_date',
        'withdrawable',
        'accum_charge',
        'wallet',
        'overdraft',
        'missed_day',
        'status_id',
        'hasAgent'
    ];
    public function esusu_type()
    {
        return $this->belongsTo(esusu_types::class,'esusu_type_id','id');
    }
    public function wb_account_details()
    {
        return $this->belongsTo(wb_account_details::class, 'wb_account_detail_id', 'id');
    }

}
