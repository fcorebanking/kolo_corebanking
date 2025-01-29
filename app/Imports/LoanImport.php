<?php

namespace App\Imports;

use App\Models\ImportLoan;
use App\Models\User;
use App\Models\Loan;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
class LoanImport implements ToModel
{
    /**
    * @param Collection $collection
    */
    public function model(array $row)
    {
        if(isset($row[1])){
                if($row[0] != 'SN' && $row[1] != 'loanref' && $row[2] != 'customer' ){
                $user =User::where('virtual_account', $row[1] )->first();
                if($user){
                $loan =Loan::where('ref', $row[17] )->count();
                    if($loan ==0){
                        return new ImportLoan(['loanref'=>$row[17],'customer'=>$row[1],'loan_type'=>$row[2],'principal'=>$row[3],'Duration'=>$row[4],'DurationPeriod'=>$row[5],'period'=>$row[6],'loanFeeMethod'=>$row[7]??1,'loanFee'=>$row[8]??0,'managementFee'=>$row[9]??0,'managementFeeMethod'=>$row[10]??1,'keep_as_past_onMissed_onExtend'=>$row[11]??0,'ExtendDuration'=>$row[12]??0,'missedFee'=>$row[13]??0,'guarantorOption'=>2,'auto_pay'=>$row[14]??1,'remark'=>$row[15]??'none','paid'=>$row[16]??0,'status'=>'pending','company_id'=>auth()->user()->company_id]);
                    }
                }
            }
        }
    }
}
