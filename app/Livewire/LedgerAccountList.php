<?php
namespace App\Livewire;

use App\Http\Controllers\TestController;
use App\Models\LedgerAccountModel;
use Livewire\Component;

class LedgerAccountList extends Component
{
    public $category_name, $page_name, $has_scrollspy, $alt_menu, $records, $response, $responseStatus;
    public function mount()
    {
        $this->view();
        if (! cando('root')) {
            abort(409);
        }

    }

    public function render()
    {
        $this->category_name    = 'accounting';
        $this->page_name        = 'ledgerAccountList';
        $this->page_menu        = 'ledgerAccountList';
        $this->has_scrollspy    = 0;
        $this->scrollspy_offset = '';
        $this->alt_menu         = 0;
        return view('livewire.ledger-account-list')->layout('layouts.app', [
            'category_name' => $this->category_name,
            'page_name'     => $this->page_name,
            'has_scrollspy' => $this->has_scrollspy,
            'alt_menu'      => $this->alt_menu,
        ]);
    }
    public function view()
    {
        $this->records = LedgerAccountModel::where('company_id', auth()->user()->company_id)->get();
    }
    public function edit($id)
    {
        // Handle the edit action for the record with the given $id
        redirect('/company/accounting/edit-ledger-account/' . $id);

    }

    public function delete($id)
    {
        // Handle the delete action for the record with the given $id
        if (LedgerAccountModel::where(['id' => $id, 'company_id' => auth()->user()->company_id])->where('deleteable', 1)->delete()) {
            $this->response       = 'Ledger deleted successfully!';
            $this->responseStatus = 1;
            $this->view();
        } else {
            $this->response       = 'Ledger fail to delete!';
            $this->responseStatus = 2;
        }
    }
    public function resync()
    {
        $resync = (new TestController)->resetImport();
        (new TestController)->syncTransaction();
        $this->view();
        $this->response       = 'Ledger Reset successfully!';
        $this->responseStatus = 1;
        return $resync;
    }
    public function resyncTransaction()
    {
        $resync               = (new TestController)->syncTransaction();
        $this->response       = 'Transaction ledger updated successfully!';
        $this->responseStatus = 1;
        return $resync;
    }

}
