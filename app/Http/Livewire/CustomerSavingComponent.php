<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Customersavings ;
use App\Models\StaffWallet ;
use App\Models\wb_account_details ;
use App\Models\company_settings ;
use Illuminate\Support\Facades\DB;

use App\Models\User ;
use App\Models\CompanyWalletTXN ;


use App\Models\Pendingcustomersaving;


class CustomerSavingComponent extends Component
{
    public $isButtonDisabled = false;

    public $auth,$page_menu,$search,$scrollspy_offset,$category_name,$page_name,$has_scrollspy,$alt_menu,$response,$responseStatus,$category, $totalTargetAmount=0,$totalCurrentBalance=0,$totalExpectedBalance=0,$totalAccount=0, $totalAccountData,$accountRepaymentData,$hideTable=false,$name,$frequency,$selectPeriod,$duration,$target,$method,$customer,$staff,$selectedTargetSaving,$interest,$commission;
    public function mount(){
        $this->auth = auth()->user();
        $this->setDashboard();
        $this->staff =  User::where(['company_id' => auth()->user()->company_id, 'user_type_id' => 2,'status_id'=>1])->select('id','first_name', 'last_name', 'email', 'uuid', 'phone', 'profile_pic', 'username', 'status_id', 'branch_id')->get(); //->whereDate('start_date','>=',date('Y-m-d'))

        $companysetting = company_settings::where('company_id',auth()->user()->company_id)->first();//update(['interest'=>$this->interest,'commission'=>$this->commission]);
        if($companysetting){
            $this->commission=$companysetting->target_saving_commission;
        $this->interest=$companysetting->target_saving_interest;
        }
    }
    public function render()
    {
        $this->category_name='custom-target-savings';
        $this->page_name='custom-target-savings';
        $this->page_menu='custom-target-savings';
        $this->has_scrollspy=0;
        $this->scrollspy_offset='';
        $this->alt_menu=0;
        $this->loadData();

        return view('livewire.customer-saving-component')->layout('layouts.app',[
            'category_name' => $this->category_name,
            'page_name' => $this->page_name,
            'page_menu' => $this->page_menu,
            'has_scrollspy' => $this->has_scrollspy,
            'alt_menu' => $this->alt_menu,
        ]);
    }
    public function setDashboard(){
        $queryTargetTotal=$totalCurrent =$totalExpected = Pendingcustomersaving::with(['customersaving.user' => function ($query) {
            $query->where('company_id', $this->auth->company_id);
        }])
        ->whereHas('customersaving.user', function ($query) {
            $query->where('company_id', $this->auth->company_id);
        });
        $this->totalTargetAmount = $queryTargetTotal->sum('amount');
        $this->totalCurrentBalance =$totalCurrent->where('status','complete')->sum('amount');
        $this->totalExpectedBalance =$totalExpected->whereDate('paymentDate','<=', date('Y-m-d'))->sum('amount');
        $this->totalAccount = Customersavings::with('user')
        ->whereHas('user', function ($query) {
            $query->where('company_id', $this->auth->company_id);
        })->count();
    }
    public function loadData(){

        if($this->search){
            $this->totalAccountData =
            Customersavings::with('user')
                ->whereHas('user', function ($query) {
                    $query->where('company_id', $this->auth->company_id)
                          ->where(function ($query) {
                              $query->where('email', 'like', '%' . $this->search . '%')
                                    ->orWhere('first_name', 'like', '%' . $this->search . '%')
                                    ->orWhere('last_name', 'like', '%' . $this->search . '%')
                                    ->orWhere('phone', 'like', '%' . $this->search . '%')
                                    ->orWhere('virtual_account', 'like', '%' . $this->search . '%');
                          });
                })
                ->get();
        } else {
            $this->totalAccountData =
            Customersavings::with('user')
                ->whereHas('user', function ($query) {
                    $query->where('company_id', $this->auth->company_id);
                })
                ->get();
        }

    }
    public function repaymentData($id){
        $this->selectedTargetSaving=$id;
        $this->hideTable =true;
        $this->accountRepaymentData = Pendingcustomersaving::where(['customersaving_id'=>$id])->get();
    }
    public function back(){
        $this->hideTable =false;

        $this->accountRepaymentData = null;
        $this->loadData();
    }
    public function liquedateBTN($id){
        if(!cando('root')) abort(409) ;
        $customersavings =Customersavings::where('id',$id)->first();
        if($customersavings){
            $totalPaid = Pendingcustomersaving::where(['customersaving_id'=>$customersavings->id,'status'=>'complete'])->sum('amount');
            $wb_account_details = wb_account_details::where(['user_id' => $customersavings->user_id])->with('user')->first();
            $amount =$customersavings->balance;
            $balanceBefore = $wb_account_details->user_wallet->balance;
            $balanceAfter = $balanceBefore + $amount;
            $txnType =getTransactionIdByName("Cash Deposit")->id;
            $ledger_cr=null;
            $ledger_dr=null;
            $ledger_Settings = get_CR_DR_TXN_Type_Ledger($txnType, $this->auth->company_id);
            if($ledger_Settings){
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }
            $data = [
                "balance_before" => $balanceBefore,
                "amount" =>  $amount,
                "description" => "Target Savings Liquidated",
                "account_id" => $wb_account_details->id,
                "branch_id" => $this->auth->branch_id,
                "transaction_status_id" => 1,
                "transaction_reference" =>  generate_txnID(),
                "transaction_type_id" => $txnType,
                "credit_debit_type_id" => 1,
                "transaction_remarks_id" => 2,
                "balance_after" => $balanceAfter,
                "created_date"  => date("Y-m-d"),
                "created_by" => $this->auth->id,
                "ledger_cr"=>$ledger_cr,
                "ledger_dr"=>$ledger_dr,
                "instrumentCode"=>null
            ];
            $data['userParamType']=2;
            $data['userId'] =  $wb_account_details->id;
            $data['remark'] =2;
            $data['chargeamt'] =0;
            $data['companyVault'] =null;
            $CreditCustomer = DebitCreditcustomer($data);
            if(!$CreditCustomer['status'] ){
                \logInfo($CreditCustomer,  'Customer Saving Error');
            }
                $customersavings->balance = 0;
                $customersavings->status ='complete';
                $customersavings->save();

                if($customersavings->agent_id){
                    $agent = User::where('id',$customersavings->agent_id)->first();
                    if($agent){
                        $staff_wallet = $agent->staff_wallet;
                        $balance = $staff_wallet->balance;
                        $commission =(($this->commission/100)*$amount);
                        $balance +=$commission;
                        StaffWallet::find($staff_wallet->id)->update(['balance'=>$balance]);
                        CompanyWalletTXN::create(['company_id'=>auth()->user()->company_id, 'staff_id'=>$staff_wallet->id, 'amount'=>$commission]);
                    }
                }
                $this->response ="Successful";
                $this->responseStatus =1;
                $this->back();
        $this->loadData();

        }
    }
public function save(){
company_settings::where('company_id',auth()->user()->company_id)->update(['target_saving_interest'=>$this->interest,'target_saving_commission'=>$this->commission]);
$this->response ="Settings saved";
$this->responseStatus =1;
}
}
