<?php
namespace App\Livewire;

use App\Models\Expenses;
use App\Models\LedgerAccountModel;
use App\Models\OtherIncome;
use Livewire\Component;

class LedgerDetails extends Component
{
    public $routeID, $category_name, $page_name, $has_scrollspy, $alt_menu, $to, $from, $branchID, $currentDate, $records, $totalDebit = 0, $totalcredit = 0, $ledgerName, $columnTotal = 0, $expenses, $otherincome;
    public function mount($id = 0)
    {
        $this->routeID = $id;
    }
    protected $listeners = [
        'fromUpdated'     => 'updateFrom',
        'toUpdated'       => 'updateTo',
        'branchIDUpdated' => 'UpdatedBranchID',
        'toledgerAccount' => 'UpdatedledgerAccount',
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
    public function UpdatedledgerAccount($ledgerAccount)
    {

        $this->routeID = $ledgerAccount;
        // Perform any necessary actions based on the updated 'to' value
    }
    public function render()
    {
        $this->category_name    = 'accounting';
        $this->page_name        = 'LedgerDetils';
        $this->page_menu        = 'LedgerDetils';
        $this->has_scrollspy    = 0;
        $this->scrollspy_offset = '';
        $this->alt_menu         = 0;
        $this->calculate();
        $data = [
            'category_name'    => $this->category_name,
            'page_name'        => $this->page_name,
            'page_menu'        => $this->page_menu,
            'has_scrollspy'    => $this->has_scrollspy,
            'scrollspy_offset' => '',
            'alt_menu'         => $this->alt_menu,
        ];
        return view('livewire.ledger-details')->layout('layouts.app', $data);
    }
    public function calculate()
    {
        $this->records = [];
        $ledgers       = LedgerAccountModel::where(['company_id' => auth()->user()->company_id, 'code' => $this->routeID])->first();
        if ($ledgers) {
            $this->expenses = Expenses::with('author')
                ->join('users', 'users.id', '=', 'expenses.created_by')
                ->where([
                    'users.company_id' => auth()->user()->company_id,
                    'ledger_dr'        => $ledgers->id,
                ])->get();
            $this->otherincome = OtherIncome::with('author')
                ->join('users', 'users.id', '=', 'other_incomes.created_by')
                ->where([
                    'users.company_id' => auth()->user()->company_id,
                    'ledger_cr'        => $ledgers->id,
                ])->get();
            $this->ledgerName = $ledgers->name;
            $this->records    = getLedgersData($ledgers->id, $this->from, $this->to, $this->branchID);

            // dd($this->records);
        }
    }
}
