<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleEsusuRepayment extends Model
{
    use HasFactory;
    protected $casts = [
        'created_at' => 'datetime',
    ];
    protected $fillable =['company_id','schedule_esusu_product_id','user_id','started_debiting_at','schedule_esusu_id','amount','type','num_of_days'];

}
