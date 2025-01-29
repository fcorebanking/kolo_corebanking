<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DomAccount;
use App\Models\wb_account_details;
use App\Models\transactions;
use App\Models\User;
use Illuminate\Support\Facades\DB;

ini_set('memory_limit', '500000M');

class DomCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dom:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cron Job to send Dom Account';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        \logInfo("Demo Cron Job is Working currently",  'Cron as Started');
        $return = 0;
        $allUser = User::where(['user_type_id'=> 1,'status_id'=>1])->get();
        foreach ($allUser as $allUserdata) {
            echo "***";
            $Wpdetails_Data = $allUserdata->wb_account_details;
            foreach ($Wpdetails_Data as $wipedata) {
                echo "Looping";

                $userdata = $allUserdata;
                $accountID = $wipedata->id;
                $insert_ray = ['wb_account_detail_id' => $accountID, 'status' => 0];
                $sessiondata = User::where('company_id',  $userdata->company_id)->first();
                $session = $sessiondata->id;
                try{
                    if($sessiondata->company->setting->dom_account_duration > 0){
                        $company_dom = $sessiondata->company->setting->dom_account_duration;
                        if (DomAccount::where($insert_ray)->count() == 0) {
                            $user_reg_date = $wipedata->user->updated_at;
                            $today = date('Y-m-d H:i:s');

                            $difdate =  diff_btw_date($user_reg_date, $today);

                            // if ($difdate >= $company_dom) {

                                $querytxn = transactions::where('transactions.account_id', '=',  $accountID)
                                    ->orderBy('transactions.id', 'desc')->take(1)->first();

                                if ($querytxn) {
                                    $difdate2 =  diff_btw_date($querytxn->created_date, $today);

                                    if ($difdate2 >= $company_dom) {
                                        $insert_ray['created_by'] = $session;
                                        $insert_ray['uuid'] = uniqid();
                                        DomAccount::create($insert_ray);
                                        $userdata->status_id = 8;
                                        $userdata->save();
                                        $accno = $userdata->virtual_account;
                                        $todayx = date('Y-m-d');
                                        echo "\n DOne";
                                        // $msg = "We've noticed that your account No $accno has been inactive for over $difdate2 consecutive days. According to our terms of service, your account is now marked as dormant ($todayx). From the moment your account has been marked as dormant, you'll have $company_dom days to stop the process.";
                                        $msg = "We've noticed that your account No $accno has been inactive for over $difdate2 consecutive days and account has been marked as dormant.";
                                        // $return = sendSMS($wipedata->id, $msg, true, true, 'Dorm Account');
                                        \logInfo($return,"Dom Cron Job Return");
                                    }
                                }
                            // }
                        }
                    }else{
                    \logInfo( "Dom Error company  $userdata->company_id", "Dom not set");

                    }
                }catch (\Exception $e) {
                    \logInfo('Message: '.$e->getMessage(),  ' Dom Failed at Line: '.$e->getLine());

                }
            }
        }
        return 0;
    }
}
