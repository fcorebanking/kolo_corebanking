<?php

namespace App\Imports;

use App\Models\tempuser;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
class UsersImport implements ToModel
{
    public function model(array $row)
    {
        if(isset($row[1])){

            // if($row[0] != 'SN' && $row[1] != 'Full Names' && $row[2] != 'Account Number' && $row[3] != 'Username'){
                if($row[0] != 'SN' && $row[1] != 'Full Names' && $row[2] != 'Account Number' ){
                $row[1] = str_replace('Null','',$row[1]);
                $name_array = explode(" ",$row[1]);
                $lastname = $firstname = $name_array[0];
                if(count($name_array) > 1){
                    unset($name_array[0]);
                    $lastname = implode($name_array);
                }
                return new tempuser([
                    'first_name'=>$firstname,
                    'last_name'=>$lastname,
                    'email'=>$row[16],
                    'phone'=>$row[4],
                    'dob'=>$row[17],
                    'address'=>$row[6],
                    'sex'=>$row[5],
                    'old_wba_user'=>$row[19],
                    'acc_ref'=>$row[20],
                    'username'=>$row[3],
                    'bvn'=>$row[9],
                    'nin'=>$row[10],
                    'ac_no'=>$row[2],
                    'bank_code'=>$row[18],
                    'reg_date'=>$row[14],
                    'balance'=>$row[15],
                    'loanBalance'=>(gettype($row[21]) == "integer" || gettype($row[21]) =="double")?$row[21]: 0,
                    'company_id'=>auth()->user()->company_id,
                    'status_id'=>1,
                ]);

            }
        }
    }
}
