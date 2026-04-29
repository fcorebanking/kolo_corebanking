<?php
namespace App\Livewire;

use Livewire\Component;

class Topsearchfield extends Component
{
    public $showDate;
    public $showBranch;
    public $showTransactionType, $showledgerAccount;
    public $to, $from, $branchID, $ledgerAccount, $staff;

    public function mount($showDate, $showBranch, $showTransactionType, $showledgerAccount = false, $staff = false)
    {
        $this->showDate            = $showDate;
        $this->showBranch          = $showBranch;
        $this->showTransactionType = $showTransactionType;
        $this->showledgerAccount   = $showledgerAccount;
        $this->staff               = $staff;

    }
    public function updatedFrom()
    {
        $this->emit('fromUpdated', $this->from);
    }

    public function updatedTo()
    {
        $this->emit('toUpdated', $this->to);
    }
    public function updatedBranchID()
    {

        $this->emit('branchIDUpdated', $this->branchID);
    }
    public function updatedstaffID()
    {
        $this->emit('staffIDUpdated', $this->staff);
    }
    public function updatedLedgerAccount()
    {
        $this->emit('toledgerAccount', $this->ledgerAccount);
    }
    public function render()
    {
        return view('livewire.topsearchfield');
    }
}
