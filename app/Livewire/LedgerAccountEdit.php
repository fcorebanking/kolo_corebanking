<?php
namespace App\Livewire;

use App\Models\LedgerAccountModel;
use Illuminate\Validation\Rule;
use Livewire\Component;

class LedgerAccountEdit extends Component
{
    public $pageID, $category_name, $page_name, $has_scrollspy, $alt_menu, $name, $code, $branchID, $type, $access, $response, $responseStatus, $category;
    public function mount($id)
    {

        if (! cando('root')) {
            abort(409);
        }

        $auth         = auth()->user();
        $this->pageID = $id;
        $ledger       = LedgerAccountModel::where(['code' => $this->pageID, 'company_id' => $auth->company_id])->first();
        if (! $ledger) {
            return abort('404');
        }

        $this->name     = $ledger->name;
        $this->code     = $ledger->code;
        $this->access   = $ledger->access;
        $this->branchID = $ledger->branch_id;
        $this->category = $ledger->category;
    }

    public function render()
    {
        $this->category_name    = 'accounting';
        $this->page_name        = 'ledgerAccountEdit';
        $this->page_menu        = 'ledgerAccountEdit';
        $this->has_scrollspy    = 0;
        $this->scrollspy_offset = '';
        $this->alt_menu         = 0;

        return view('livewire.ledger-account-edit')->layout('layouts.app', [
            'category_name' => $this->category_name,
            'page_name'     => $this->page_name,
            'has_scrollspy' => $this->has_scrollspy,
            'alt_menu'      => $this->alt_menu,
        ]);
    }
    public function store()
    {

        try {
            $auth          = auth()->user();
            $validatedData = $this->validate([
                'access'   => 'required',
                'category' => 'required',
                'code'     => [
                    'required',
                    Rule::unique('ledger_accounts')->where(function ($query) use ($auth) {
                        return $query->where('company_id', $auth->company_id)
                            ->where('code', '!=', $this->pageID);
                    }),
                ],
                'name'     => [
                    'required',
                    Rule::unique('ledger_accounts')->where(function ($query) use ($auth) {
                        return $query->where('company_id', $auth->company_id)
                            ->where('code', '!=', $this->pageID);
                    }),
                ],
            ]);

            if ($this->category == "Drawings" || $this->category == "Expenses" || $this->category == "Asset") {
                $this->type = 'debit';
            } elseif ($this->category == "Liability" || $this->category == "Equity" || $this->category == "Revenue") {
                $this->type = 'credit';
            } else {
                $this->response       = "Category Error!";
                $this->responseStatus = 2;
                return false;
            }
            $addAccount = LedgerAccountModel::where(['code' => $this->pageID, 'company_id' => $auth->company_id])->first();
            if (! $addAccount) {
                $this->response       = "Error Processing request!";
                $this->responseStatus = 2;
                return false;
            }
            $addAccount->access     = $this->access;
            $addAccount->code       = $this->code;
            $addAccount->name       = $this->name;
            $addAccount->created_by = $auth->id;
            $addAccount->deleteable = 1;
            $addAccount->type       = $this->type;
            $addAccount->company_id = $auth->company_id;
            $addAccount->branch_id  = $this->branchID;
            $addAccount->category   = $this->category;
            $addAccount->save();
            $this->response       = $addAccount->name . 'Ledger added successfully!';
            $this->responseStatus = 1;
        } catch (Exception $e) {
            $this->response       = "Error Processing request";
            $this->responseStatus = 2;
        }
    }
}
