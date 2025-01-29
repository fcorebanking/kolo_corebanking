<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\LedgerAccountModel ;

class LedgerAccount extends Component
{
    public $category_name,$page_name,$has_scrollspy,$alt_menu,$name,$code,$branchID,$type,$access,$response,$responseStatus,$category;
    public function mount(){
        if(!cando('root')) abort(409) ;
    }
    public function render()
    {
        $this->category_name='accounting';
        $this->page_name='ledgerAccount';
        $this->page_menu='ledgerAccount';
        $this->has_scrollspy=0;
        $this->scrollspy_offset='';
        $this->alt_menu=0;
        return view('livewire.ledger-account')->layout('layouts.app',[
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
                'access' => 'required',
                'category' => 'required',
                'code' => 'required|numeric|unique:ledger_accounts,code,NULL,id,company_id,' . $auth->company_id,
                'name' => 'required|unique:ledger_accounts,name,NULL,id,company_id,' . $auth->company_id,
            ]);
            if($this->category == "Drawings" || $this->category == "Expenses" || $this->category == "Asset"){
                $this->type='debit';
            }elseif($this->category == "Liability" || $this->category == "Equity" || $this->category == "Revenue"){
                $this->type='credit';
            }else{
                $this->response ="Category Error!";
                $this->responseStatus =2;
                return false;
            }
            $addAccount =new LedgerAccountModel;
            $addAccount->access = $this->access;
            $addAccount->code= $this->code;
            $addAccount->name= $this->name;
            $addAccount->created_by= $auth->id;
            $addAccount->deleteable= 1;
            $addAccount->type=$this->type;
            $addAccount->company_id= $auth->company_id;
            $addAccount->branch_id =$this->branchID;
            $addAccount->category =$this->category;
            $addAccount->save();
            $this->response =$addAccount->name.' Ledger added successfully!';
            $this->responseStatus =1;
        }catch(Exception $e){
            $this->response ="Error Processing request";
            $this->responseStatus =2;
        }
    }
}
