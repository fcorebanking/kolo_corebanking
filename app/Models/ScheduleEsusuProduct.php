<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleEsusuProduct extends Model
{
    use HasFactory;
    protected $fillable =['name','start_date','end_date','status','minimun','maximal','company_id','circle_out_charge','withdraw_out_charge','agent_commission','description','t_c'];
}
