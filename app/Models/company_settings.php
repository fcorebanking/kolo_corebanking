<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class company_settings extends Model
{

    use HasFactory;
    protected $fillable = [
        'smsapi_id',
        'allsms_state',
        'target_saving_interest',
        'target_saving_commission', 
        'target_savings_early_withdrawal_fee_percentage', 
        'target_savings_interest_rate', 
        'campaign_fee_percentage', 
        'ajo_creation_fee', 
        'ajo_maturity_fee_percentage',
        'fixed_saving_interest',
        'fixed_saving_commission',
        'fixed_savings_early_withdrawal_fee_percentage',
        'fixed_savings_interest_rate'
        
    ];
    public function plan()
    {
        return $this->belongsTo(plans::class,'plan_id','id');
    } 
    public function company()
    {
        return $this->belongsTo(company::class,'company_id','id');
    }
    public function smsapi()
    {
        return $this->belongsTo(smsapi::class,'smsapi_id','id');
    }
    public function backdate()
    {
        return $this->hasOne(back_date_status::class,'id','back_date_status_id');
    }
    public function tellerOption()
    {
        return $this->hasOne(teller_option::class,'id','teller_option_id');
    }
}
