<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class loan_product extends Model
{ 
    use HasFactory;
    protected $fillable =[
        'company_id','product_name','interest_rate','minPrincipal',
    'DefaultPrincipal','maxPrincipal','minInterest','DefaultInterest',
'maxInterest','loanFeeMethod','loanFee','managementFeeMethod','managementFee',
'minDuration','DefaultDuration','maxDuration',
'repayment_cycle_id','approximate_status','auto_pay','minExtendDuration',
'DefaultExtendDuration','maxExtendDuration','missedFee','keep_as_past_onMissed_onExtend','status_id'];

}
