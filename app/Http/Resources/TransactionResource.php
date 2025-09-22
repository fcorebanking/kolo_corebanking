<?php
namespace App\Http\Resources;

use App\Models\transaction_remarks;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $transaction_remark = transaction_remarks::where('id', $this->transaction_remarks_id)->first();
        return [

            "account_id"             => $this->account_id,
            "balance_before"         => $this->balance_before,
            "balance_after"          => $this->balance_after,
            "amount"                 => $this->amount,
            "company_balance_before" => $this->company_balance_before,
            "company_amount"         => $this->company_amount,
            "company_balance_after"  => $this->ompany_balance_after,
            "withdrawal_charge"      => $this->withdrawal_charge,
            "sms_charge"             => $this->sms_charge,
            "loan_id"                => $this->loan_id,
            "branch_id"              => $this->branch_id,
            "company_id"             => $this->company_id,
            "description"            => $this->description,
            "system_description"     => $this->system_description,
            "parent_transaction_id"  => $this->parent_transaction_id,
            "transaction_reference"  => $this->transaction_reference,
            "transaction_status"     => $transaction_remark->remark_class,
            "transaction_remarks_id" => $this->transaction_remarks_id,
            "transaction_type_id"    => $this->transaction_type_id,
            "credit_debit_type_id"   => $this->credit_debit_type_id,
            "created_by"             => $this->created_by,
            "modified_by"            => $this->modified_by,
            "created_date"           => $this->created_date,
            "loan_repayment_id"      => $this->loan_repayment_id,
            "instrumentCode"         => $this->instrumentCode,
            "ledger_cr"              => $this->ledger_cr,
            "ledger_dr"              => $this->ledger_dr,
            "initiator"              => $this->initiator,
            "currency_id"            => $this->currency_id,

        ];
    }
}
