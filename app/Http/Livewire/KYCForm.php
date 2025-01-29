<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\wb_account_details;

class KYCForm extends Component
{
    use WithFileUploads;

    public $account_number;
    public $bvn;
    public $passport ;//= asset('ready/images/secure.png');
    public $account = null;
    public $validationStatus = false;
    public $nextIsBvn = false;
    public $nextIsFace = false;
    public $imageVerifying = false;
    public $ids = [];
    public $tier;
    public $id_card_type;
    // public $bill_type;
    public $id_card_front;
    public $id_card_back;
    public $id_card_serial_number;
    public $proof_of_address_type;
    public $proof_of_address;
    public $next_of_kin;
    public $next_of_kin_phone_number;
    public $next_of_kin_address;
    public $source_of_income;
    public $message;
    public $alreadyPending = null;
    public function render()
    {
        return view('livewire.k-y-c-form');
    }
    public function verifyAccountNumber(){
        $wb_account_details = wb_account_details::where('virtual_accnum',$this->account_number)->first();

    }
}
