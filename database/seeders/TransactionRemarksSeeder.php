<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use  App\Models\transaction_remarks;
class TransactionRemarksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $types = [

            ['transaction_remark' => 'Pending','remark_class'=>'warning'],
            ['transaction_remark' => 'Successful','remark_class'=>'success'],
            ['transaction_remark' => 'Failed','remark_class'=>'danger'],
            ['transaction_remark' => 'Waiting-Approval','remark_class'=>'warning'],
        ];
        for($i=0;$i<count($types);$i++){
            $type = $types[$i]['transaction_remark'];
            $check_transactions = transaction_remarks::where('transaction_remark',$type)->count();
            if($check_transactions==0){
                DB::table('transaction_remarks')->insert($types[$i]);
            }
        }
    }
}
