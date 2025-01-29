<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportLoan extends Model
{
    use HasFactory;
    protected $fillable =[
        'loanref','customer','branch','officer','loan_products','loan_type','principal','Duration','DurationPeriod','period','RepaymentAmt','loanFeeMethod','loanFee','managementFee','managementFeeMethod','keep_as_past_onMissed_onExtend','ExtendDuration','missedFee','guarantorOption','auto_pay','remark','status','company_id','paid'
    ];
}
