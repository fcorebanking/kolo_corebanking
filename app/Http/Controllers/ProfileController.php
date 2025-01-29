<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;

use App\Models\branch;
use App\Models\StaffWallet;

use App\Models\status;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\wb_account_details;
use App\Models\user_wallet;

class ProfileController extends Controller
{
    public function index(){
        $companyBranchSetting =auth()->user()->company->setting->allow_edit_branch_subscription_status;
        $branch_subscription_status_id =14;
        $data = [
            'category_name' => 'form',
            'page_name' => 'basic',
            'page_menu' => 'userprofile',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'branch'=>branch::where(['company_id'=>auth()->user()->company_id])->where('status_id','!=',$branch_subscription_status_id)->get(),
            'status' => ($companyBranchSetting==0)? status::where('id', 1)->orwhere('id', 2)->get():status::where('id', 1)->orwhere('id', 2)->orwhere('id', 14)->get(),
        ];
        return view('company.staff.editprofile')->with($data);
    }

    public function withdrawStaffSettlement(Request $request){
        try{


        $validator = Validator::make($request->all(), [
            'amount' => 'required',
        ]);
        if(!$validator->passes()){
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {
                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= $message;
                }
            }
            return redirect()->back()->with('fail',$errors);
          //  return response()->json(["status" => 'bg-danger', "token" => $token, 'message' => "$errors"]);
        }
        $amount = $request->amount;
        $user_wallet = user_wallet::where('id',auth()->user()->staff_settlement_wallet->id)->first();
        if($user_wallet){
            $balanceBefore = $user_wallet->balance;

            $commission = auth()->user()->wallet->commission;

            if($commission < $amount){
                return redirect()->back()->with('fail','Insufficient balance');
            }
            $ledg =getTransactionIdByName('Cash Deposit');
            $get_ledger_setting_Commission = get_CR_DR_TXN_Type_Ledger($ledg->id, auth()->user()->company_id);
            if (!$get_ledger_setting_Commission) {
                return response()->json(["status" => 0, "message" => "Ledger error: Transaction failed! "]);
            }
            $balanceAfter =$balanceBefore + $amount;
            $data = [
                "balance_before" => $balanceBefore,
                "amount" =>  $amount,
                "description" => "deposit from Comission Withdraw",
                "account_id" => $user_wallet->wb_account_detail_id,
                "branch_id" => auth()->user()->branch_id,
                "transaction_status_id" => 1,
                "transaction_reference" => generate_txnID(),
                "transaction_type_id" => $ledg->id, //debit 7
                "credit_debit_type_id" => 1, //debit 2
                "transaction_remarks_id" => 2, //success 2
                "balance_after" => $balanceAfter,
                "created_date"  => date('Y-m-d'),
                "created_by" => auth()->user()->id,
                "instrumentCode" => null,
                "ledger_cr" => $get_ledger_setting_Commission->ledger_cr, //
                "ledger_dr" => $get_ledger_setting_Commission->ledger_dr,
                "initiator" => null,
                "userParamType"=>2,
                "userId"=> $user_wallet->wb_account_detail_id,
                "remark"=>2,
                "chargeamt"=>0,
                "companyVault"=>null,
                "sendSMS"=>0,
            ];
            $staffwallet = StaffWallet::where('user_id',auth()->user()->id)->first();
            $staffwallet->commission = $commission - $amount;
            $staffwallet->save();
            $CreditCustomer = DebitCreditcustomer($data);
            if (!$CreditCustomer['status']) {
                DB::rollBack();
                return $CreditCustomer;
            }
            return redirect()->back()->with('success','Wallet Funded');

        }else{
            return redirect()->back()->with('fail','Wallet error');
        }
    }catch(Exception $e){
        return redirect()->back()->with('fail',$e->getMessage());
    }
    }

    public function SaveProfile(Request $request){
        $validator =null;
        if($request->status == 'profile'){
            $validator = Validator::make($request->all(), [
                'address' => 'required',
                'branch'=>'required',
            ]);
            if(!$validator->passes()){
                $errors = '';
                foreach ($validator->messages()->getMessages() as $field_name => $messages) {
                    // Go through each message for this field.
                    foreach ($messages as $message) {
                        $errors .= $message;
                    }
                }
                return redirect()->back()->with('fail',$errors);
              //  return response()->json(["status" => 'bg-danger', "token" => $token, 'message' => "$errors"]);
            }
            if($request->file('avatar')){
                try {
                    //code...
                    $user = User::where('id',auth()->user()->id)->first();
                    $doc = $request->file('avatar');

                    $new_name = rand() . "." . $doc->getClientOriginalExtension();

                    $file1 = $doc->storeAs("public/company/customer/profile_pic/$user->id", $new_name);
                    $user->update([
                        'profile_pic' => $file1
                    ]);
                } catch (\Throwable $th) {
                    //throw $th;
                }
            }
            $branch = Crypt::decryptString($request->branch);
            $settlementid = null;
            if($request->settlement_wallet_id){
                $wb_account_details = wb_account_details::where(['virtual_accnum' => $request->settlement_wallet_id])->first();
                if($wb_account_details){
                    $settlementid = $wb_account_details->user_wallet->id;
                }else{
                    return redirect()->back()->with('fail','Error: Invalide account');
                }
            }
            User::where('id',auth()->user()->id)->update(['address'=>$request->address,'branch_id'=>$branch, 'settlement_wallet_id'=>$settlementid]);
            return redirect()->back()->with('success','User Profile Updated!');

        }else if( $request->status =='password'){
            $validator = Validator::make($request->all(), [
                'current_password' => 'required',
                'new_password' => 'required',
                'comfirm_password' => 'required',
            ]);
            if(!$validator->passes()){
                $errors = '';
                foreach ($validator->messages()->getMessages() as $field_name => $messages) {

                    // Go through each message for this field.
                    foreach ($messages as $message) {
                        $errors .= $message;
                    }
                }
                return redirect()->back()->with('fail',$errors);

              //  return response()->json(["status" => 'bg-danger', "token" => $token, 'message' => "$errors"]);
            }
            if($request->new_password != $request->comfirm_password){
                  return redirect()->back()->with('fail','Password Mis-Match');
            }
            if (!(Hash::check($request->get('current_password'), auth()->user()->password))) {

                return redirect()->back()->with('fail','Your Current password does not matches with the password you provided. Please try again.');

            } else {
                $user = User::find(auth()->user()->id);
                $user->password = Hash::make($request->get('new_password'));
                $user->update();
                if ($user) {

                    return redirect()->back()->with('success','Password updated successfully!');

                } else {

                    return redirect()->back()->with('fail','Something went wrong!');

                }
            }

        }else if( $request->status =='pin'){
            $validator = Validator::make($request->all(), [
                'mypassword' => 'required',
                'old_pin' => 'required',
                'new_pin' => 'required',
            ]);
            if(!$validator->passes()){
                $errors = '';
                foreach ($validator->messages()->getMessages() as $field_name => $messages) {

                    // Go through each message for this field.
                    foreach ($messages as $message) {
                        $errors .= $message;
                    }
                }
                return redirect()->back()->with('fail',$errors);

              //  return response()->json(["status" => 'bg-danger', "token" => $token, 'message' => "$errors"]);
            }
            if (!($request->old_pin == auth()->user()->pin) && (auth()->user()->pin != null)) {

                return redirect()->back()->with('fail','Your Current pin does not matches with the pin you provided. Please try again.');

            }elseif (!(Hash::check($request->get('mypassword'), auth()->user()->password))) {
                return redirect()->back()->with('fail','Your Current password does not matches with the password you provided. Please try again.');

            }else{
                $user = User::find(auth()->user()->id);
                $user->pin = $request->get('new_pin');
                $user->update();
                return redirect()->back()->with('success','Pin updated successfully!');

            }
        }else{
            return redirect()->back()->with('fail','Validation Error!');

        }

    }

}
