<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\LedgerSettings;
use App\Models\transactions;
use App\Models\LedgerAccountModel;

class LedgerSettingsComponent extends Component
{
    public $category_name,$page_name,$has_scrollspy,$alt_menu,$response,$responseStatus,$ledger_cr,$ledger_dr,$page,$records;
    public function mount(){
        if(!cando('root')) abort(409) ;
        $this->show();

    }
    public function render()
    {
        $this->category_name='accounting';
        $this->page_name='ledgerAccountSetting';
        $this->page_menu='ledgerAccountSetting';
        $this->has_scrollspy=0;
        $this->scrollspy_offset='';
        $this->alt_menu=0;
        return view('livewire.ledger-settings-component')->layout('layouts.app',[
            'category_name' => $this->category_name,
            'page_name' => $this->page_name,
            'has_scrollspy' => $this->has_scrollspy,
            'alt_menu' => $this->alt_menu,
        ]);
    }
    public function store(){

        try{
            $auth = auth()->user();
            $validatedData = $this->validate([
                'ledger_cr' => 'required',
                'ledger_cr' => 'required',
                'page' => 'required',
            ]);
            if($this->ledger_cr != $this->ledger_dr){
                $LedgerAccountModelCR = LedgerAccountModel::findorfail($this->ledger_cr);
                $LedgerAccountModelDR = LedgerAccountModel::findorfail($this->ledger_dr);
                if(!$LedgerAccountModelCR || !$LedgerAccountModelDR){
                    $this->response ="Invalide Ledger Account!";
                    $this->responseStatus =2;
                    return false;
                }
                if($LedgerAccountModelCR->type == $LedgerAccountModelDR->type){
                    //$this->response ="Same category of ledger not allowed, for you trial balance to be balance select an opposit ledger!";
                    //$this->responseStatus =2;
                    // return false;
                }
            }
            $checkDuplicate = LedgerSettings::where(['transaction_type_id'=>$this->page,'company_id'=>$auth->company_id])->count();
            if($checkDuplicate > 0){
                $this->response ="Settings for this transaction type already set!";
                $this->responseStatus =2;
                return false;
            }
            $addAccount =new LedgerSettings;
            $addAccount->transaction_type_id= $this->page;
            $addAccount->ledger_cr= $this->ledger_cr;
            $addAccount->ledger_dr= $this->ledger_dr;
            $addAccount->company_id= $auth->company_id;
            $addAccount->created_by= $auth->id;

            $addAccount->save();
            $this->show();
            $this->response ='Ledger added successfully!';
            $this->responseStatus =1;
        }catch(Exception $e){
            $this->response ="Error Processing request";
            $this->responseStatus =2;
        }
    }
    public function show(){
        $this->records = LedgerSettings::where('company_id',auth()->user()->company_id)->get();
    }
    public function delete($id){
        LedgerSettings::where(['id'=>$id,'company_id'=>auth()->user()->company_id])->delete();
        $this->response ='Ledger Deleted successfully!';
        $this->responseStatus =1;
    }
}
