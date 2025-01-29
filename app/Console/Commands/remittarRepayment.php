<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
ini_set('memory_limit', '500000M');

class remittarRepayment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $rem = new RemittaController;
        $request = new Request;
        $RemitaMandate =  RemitaMandate::where('status','Active')->get();
        if($RemitaMandate){
            foreach($RemitaMandate as $RemitaMandateData){
		        DB::beginTransaction();
                    $request->merge([
                        'mandateRef'=>$RemitaMandateData->mandateReference,
                        'customerId'=> $RemitaMandateData->customerId
                    ]);
                    $response = $rem->viewMandate($request);
                    $rec = json_decode($response);
                    if($rec->status == "success" && $rec->hasData ==true){
                        $data = $rec->data->repayment;
                        $totalpaid = 0;
                        $repayment = 0;
                        foreach($data as $repaymentData){
                            if($repaymentData->paymentstatus == "paid"){
                                $repayment = $repaymentData->transactionamount;
                                $totalpaid = $totalpaid + $repaymentData->transactionamount;
                            }
                        }
                            //code herer
                    }
                }
            }
        return 0;
    }
}
