<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\User ;
use App\Models\wb_account_details ;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\SavingsController;
class BulkTransactionComponent extends Component
{
    use WithFileUploads;
    public $file ;
    public $typeloader=['tt'];
    public $category_name,$page_name,$page_menu,$has_scrollspy,$alt_menu,$transactionType,$response,$responseStatus,$sentOTP,$otp,$type;
    public function render()
    {
        $this->category_name='savings';
        $this->page_name='BuckTransactions';
        $this->page_menu='BuckTransactions';
        $this->has_scrollspy=0;
        $this->scrollspy_offset='';
        $this->alt_menu=0;
        return view('livewire.bulk-transaction-component')->layout('layouts.app',[
            'category_name' => $this->category_name,
            'page_name' => $this->page_name,
            'page_menu' => $this->page_menu,
            'has_scrollspy' => $this->has_scrollspy,
            'alt_menu' => $this->alt_menu,
        ]);
    }
    public function sendOTP(){
        $this->validate([
            'file' => 'required', // Validate file type and size
        ]);
        $token = mt_rand('111111','999999');
        $auth =auth()->user();
        $authUser = User::where('id',$auth->id)->first();
        $authUser->otp = $token;
        $currentDateTime = Carbon::now();
        $authUser->otp_expiration_date  = $currentDateTime->addMinutes(5)->format('Y-m-d H:i:s');
        $authUser->save();
        $this->otp = $token;
        $msg = "Hi,your one time pin for bulk $this->type trnasaction Token: $token";
        //sendSMS($authUser->id, $msg, true, false, $auth->company->company_name);
        $this->sentOTP =1;
    }

    public function import()
    {
        $this->validate([
            'type'=>'required|in:Debit,Credit',
            'file' => 'required', // Validate file type and size
        ]);
        $auth =auth()->user();
        $authUser = User::where('id',$auth->id)->first();
        if($authUser->otp_expiration_date < date('Y-m-d H:i:s')){
            $this->response ="OTP Expire, Resend Otp";
            $this->responseStatus =2;
            $this->sentOTP =0;
            return false;
        }
        $authUser->otp_expiration_date = date('Y-m-d H:i:s');
        $authUser->save();

        $path = $this->file->store('import');
        $contents = Storage::get($path);

        // Process the CSV file content
        // You can use a CSV parsing library or implement your own logic

        // Example: Parse the CSV content using Laravel's built-in CSV reader
        $rows = collect(explode("\n", $contents))->map(function ($row) {
            return str_getcsv($row);
        });
        $errorMSG = '';
        $numSuccess = 0;
        // Process each row of data
        foreach ($rows as $row) {
            // Do something with each row of data
            if(count($row) > 0){
                if(isset($row[0]) && isset($row[1]) && isset($row[2])){
                    if(is_numeric($row[1]) &&  is_numeric($row[2])){
                        $saveTXN;
                        $wb_account_details = wb_account_details::where(['virtual_accnum' => $row[2]])->first();
                        if(!isset($wb_account_details->user)){
                            $errorMSG = "Account Number: ".$row[2]."invalide";
                            $this->response =$errorMSG;
                            $this->responseStatus =2;
                            $this->sentOTP =0;
                            $this->file = null;
                            return false;
                        }
                        $request =new Request;
                        $request->uuid = $wb_account_details->user->uuid;
                        $request->amount = $row[1];
                        $request->desc = $row[4];
                        $request->instrumentCode = $row[3];
                        $request->account_type = $row[2];
                        $request->w_charge = $row[5];
                        $request->transaction_type_id =$this->transactionType;
                        if($this->type == 'Credit'){
                            $saveTXN = (new SavingsController)->CreditCustomer_script($request);
                        }else if($this->type == 'Debit'){
                            $saveTXN = (new SavingsController)->DebitCustomer_script($request);
                        }
                        if($saveTXN['status'] != 1){
                            $name =$row[0];
                            $errorMSG = "$numSuccess uploaded, but failed uploading from $name to ...! Reason: ".$saveTXN['message'];
                            $this->response =$errorMSG;
                            $this->responseStatus =2;
                            $this->sentOTP =0;
                            $this->file = null;
                            return false;
                        }else{
                            $numSuccess++;
                        }
                    }
                }
            }
        }
        $addon = "Transaction";
        $addon .=($numSuccess> 1)?'s':'';
        $this->response ="$numSuccess $addon Uploaded successful!";
        $this->responseStatus =1;
        $this->sentOTP =0;
        $this->file = null;
    }
    public function typeChanged()
    {
        if($this->type =='Debit'){
            $this->typeloader = [7,93,30,81,39,30];
        }else if($this->type =='Credit'){
            $this->typeloader = [1,94,78,39,30];
        }

    }

}



