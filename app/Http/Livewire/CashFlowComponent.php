<?php

namespace App\Http\Livewire;

use Livewire\Component;

class CashFlowComponent extends Component
{
    public $category_name,$page_name,$has_scrollspy,$alt_menu,$from,$to,$depositTXN,$withdrawTXN,$totaldeposit,$totalwithdraw,$currentDate,$branchID,$transactionType,$otherIncome,$expenses;
    public function render()
    {
        $this->category_name='accounting';
        $this->page_name='cashflow';
        $this->page_menu='cashflow';
        $this->has_scrollspy=0;
        $this->scrollspy_offset='';
        $this->alt_menu=0;
        $this->searchResult();
        return view('livewire.cash-flow-component')->layout('layouts.app',[
            'category_name' => $this->category_name,
            'page_name' => $this->page_name,
            'has_scrollspy' => $this->has_scrollspy,
            'alt_menu' => $this->alt_menu,
        ]);
    }
    public function searchResult(){
        $this->depositTXN =transactionReportTypes($cr_dr=1,$this->from, $this->to, $this->branchID,$this->transactionType);
        $this->withdrawTXN =transactionReportTypes($cr_dr=2,$this->from, $this->to, $this->branchID,$this->transactionType);


        $this->otherIncome =OtherIncomeSum($this->from, $this->to, $this->branchID);
        $this->expenses =ExpensesSum($this->from , $this->to , $this->branchID);
        $this->currentDate =($this->from==null && $this->to ==null)?date('Y-m-d h:i:s'):'-';
        if($this->from != null){
            $this->currentDate =$this->from.' - ';
        }
        if($this->to != null){
            $this->currentDate .=$this->to.' ';
        }
         $this->totaldeposit = $this->otherIncome;
         $this->totalwithdraw  = $this->expenses;
    }
    public function resetForm(){
        $this->from = null;
        $this->to = null;
        $this->branchID = null;
        $this->searchResult();
    }
}
