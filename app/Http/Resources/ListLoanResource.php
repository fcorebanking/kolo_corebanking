<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ListLoanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {

        return [
            'loan_status_id' => $this->loan_status_id,
            'remark'         => $this->remark,
            'modified_by'    => $this->modified_by,
            'missedLoanLeft' => $this->missedLoanLeft,
            'missedLoan'     => $this->missedLoan,
            'principal'      => $this->principal,
            'loan_ref'       => $this->ref,
            'principalLeft'  => $this->principalLeft,
            'InterestLeft'   => $this->InterestLeft,
            'loanBalance'    => $this->loanBalance,
            'account_id'     => auth()->user()->accounts->first()->id,
            "loanstatusName" => $this->loan_status_id == 1 ? "Active" : "inactive", // from loan_status table
            "loanstatustag"  => $this->loan_status_id == 1 ? "green" : "Red",

        ];
    }
}
