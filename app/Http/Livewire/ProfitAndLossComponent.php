<?php

namespace App\Http\Livewire;

use Livewire\Component;

class ProfitAndLossComponent extends Component
{
    public $category_name,$page_name,$has_scrollspy,$alt_menu,$expenses,$revenue,$loss,$otherIncome,$to,$from,$branchID,$currentDate,$totalwithdraw,$totaldeposit,$totalCashLoanRepaymentInterest,$viewSelectedTransactionTypeDetails,$loading=0,$viewLoanRepayment=null,$officer=null;
    public function mount()
    {


    }

    protected $listeners = [
        'fromUpdated' => 'updateFrom',
        'toUpdated' => 'updateTo',
        'branchIDUpdated'=>'UpdatedBranchID',

    ];

    public function updateFrom($from)
    {
        $this->loading =1;
        $this->from = $from;
        $this->loading =false;

        // Perform any necessary actions based on the updated 'from' value
    }
    public function updateTo($to)
    {
        $this->loading =1;
        $this->to = $to;
        $this->loading =false;

        // Perform any necessary actions based on the updated 'to' value
    }
    // public function updateStaff($officer)
    // {
    //     $this->emit('staffIDUpdated', $this->officer);
    //     $this->loading =1;
    //     $this->officer = $officer;
    //     $this->loading =false;
    // }
    public function UpdatedBranchID($branchID)
    {
        $this->loading =1;
        $this->branchID = $branchID;
        $this->loading =false;

        // Perform any necessary actions based on the updated 'to' value
    }
    public function render()
    {
        $this->category_name='accounting';
        $this->page_name='profitAndloss';
        $this->page_menu='profitAndloss';
        $this->has_scrollspy=0;
        $this->scrollspy_offset='';
        $this->alt_menu=0;
        return view('livewire.profit-and-loss-component')->layout('layouts.app',[
            'category_name' => $this->category_name,
            'page_name' => $this->page_name,
            'has_scrollspy' => $this->has_scrollspy,
            'alt_menu' => $this->alt_menu,
        ]);
    }
    public function calculate(){
        $this->loading =1;

        $this->currentDate =($this->from==null && $this->to ==null)?date('Y-m-d h:i:s'):'-';
        $this->otherIncome =OtherIncomeSum($this->from, $this->to, $this->branchID,null,$this->officer);
        $this->expenses =ExpensesSum($this->from , $this->to , $this->branchID,null,$this->officer);
        $this->loss =AllLoss($this->from , $this->to , $this->branchID,$this->officer);
        $this->revenue =Allprofit($this->from , $this->to , $this->branchID,$this->officer);
        $this->totaldeposit = $this->otherIncome;
        $this->totalwithdraw  = $this->expenses;
        $this->totalCashLoanRepaymentInterest = allCashLoanRepayment($this->from , $this->to , $this->branchID,1,$this->officer);
        $this->loading =0;

    }
    public function resetForm(){
        $this->from = null;
        $this->to = null;
        $this->branchID = null;
        $this->calculate();

    }
    public function goBack(){
        $this->viewLoanRepayment =$this->viewSelectedTransactionTypeDetails = null;
        $this->calculate();
    }
    public function loadTransactionTypeRecord($selectedTransactionType){
        $this->loading =1;
        $this->viewSelectedTransactionTypeDetails = (getTransactionTypeData($selectedTransactionType, $this->from , $this->to , $this->branchID,$this->officer));
        $this->loading =0;
    }
    public function loadTransactionTypeRecordLoanRepayment(){
        $this->loading =1;
        $this->viewLoanRepayment = allCashLoanRepayment( $this->from , $this->to , $this->branchID,2,$this->officer);
        $this->loading =0;
    }

}
