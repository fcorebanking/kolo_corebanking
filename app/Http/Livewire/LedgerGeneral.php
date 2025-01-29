<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\LedgerAccountModel ;
class LedgerGeneral extends Component
{
    public $category_name,$page_name,$has_scrollspy,$alt_menu,$to,$from,$branchID,$currentDate,$records,$totalDebit=0,$totalcredit=0,$OtherIncomeLedger,$ExpensesReportLedger;
    public function mount(){

    }
    protected $listeners = [
        'fromUpdated' => 'updateFrom',
        'toUpdated' => 'updateTo',
        'branchIDUpdated'=>'UpdatedBranchID'
    ];
    public function updateFrom($from)
    {
        $this->from = $from;
        // Perform any necessary actions based on the updated 'from' value
    }
    public function updateTo($to)
    {
        $this->to = $to;
        // Perform any necessary actions based on the updated 'to' value
    }
    public function UpdatedBranchID($branchID)
    {
        $this->branchID = $branchID;
        // Perform any necessary actions based on the updated 'to' value
    }
    public function render()
    {
        $this->category_name='accounting';
        $this->page_name='generalLedger';
        $this->page_menu='generalLedger';
        $this->has_scrollspy=0;
        $this->scrollspy_offset='';
        $this->alt_menu=0;
        $this->calculate();
        return view('livewire.ledger-general')->layout('layouts.app',[
            'category_name' => $this->category_name,
            'page_name' => $this->page_name,
            'has_scrollspy' => $this->has_scrollspy,
            'alt_menu' => $this->alt_menu,
        ]);
    }
    public function calculate(){
        $this->totalDebit=0;
        $this->totalcredit=0;
        $ledgers =LedgerAccountModel::where('company_id',auth()->user()->company_id)->get();
        if($ledgers){
            $this->records =[];
            $this->OtherIncomeLedger = OtherIncomeLedger($this->from, $this->to, $this->branchID);
            $this->ExpensesReportLedger =ExpensesReportLedger($this->from, $this->to, $this->branchID);
            foreach($ledgers as $ledgersData){
                $data = getLedgers($ledgersData->id,$this->from, $this->to, $this->branchID);
                if($data){
                    $ExpensesSum = ExpensesSum($this->from, $this->to, $this->branchID,$ledgersData->id);
                    $OtherIncomeSum = OtherIncomeSum($this->from, $this->to, $this->branchID,$ledgersData->id);
                    $this->records[] =(['code'=>$ledgersData->code,'name'=>$ledgersData->name,'data'=>$data,'otherexpenses'=>$ExpensesSum,'otherincome'=>$OtherIncomeSum]);
                }
            }
        }
        return $this->records;
    }
}
