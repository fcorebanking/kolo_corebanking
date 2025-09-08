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
            'principalLeft'  => $this->principalLeft,
            'InterestLeft'   => $this->InterestLeft,
            'loanBalance'    => $this->loanBalance,
            "loanstatusName" => "Active", // from loan_status table
            "loanstatustag"  => "green",

        ];
    }
}
