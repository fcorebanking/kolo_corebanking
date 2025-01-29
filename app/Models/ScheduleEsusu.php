<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleEsusu extends Model
{
    use HasFactory;
    protected $casts = [
        'created_at' => 'datetime',
    ];
    protected $fillable =['company_id','schedule_esusu_product_id','user_id','started_debiting_at','amount_paid','wallet','end_date','wb_wallet_id','created_by'];
    public function User()
    {
        return $this->belongsTo(User::class);
    }
    public function Product()
    {
        return $this->belongsTo(ScheduleEsusuProduct::class,'schedule_esusu_product_id','id');
    }

}
