<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class transactions extends Model
{
    use HasFactory;
    protected $fillable = [
        "account_id",
        "balance_before",
        "balance_after",
        "amount",
        "company_balance_before",
        "company_amount",
        "company_balance_after",
        "withdrawal_charge",
        "sms_charge",
        "loan_id",
        "branch_id",
        "company_id",
        "description",
        "system_description",
        "parent_transaction_id",
        "transaction_reference",
        "transaction_remarks_id",
        "transaction_type_id",
        "credit_debit_type_id",
        "created_by",
        "modified_by",
        "created_date",
        "loan_repayment_id",
        "instrumentCode",
        "ledger_cr",
        "ledger_dr",
        "initiator","currency_id"
    ];
    public function type()
    {
        return $this->belongsTo(transaction_types::class,'transaction_type_id','id');
    }
    public function remark()
    {
        return $this->belongsTo(TransactionRemark::class,'transaction_remarks_id','id');
    }
    public function creditDebitType()
    {
        return $this->belongsTo(CreditDebitType::class);
    }
    public function author()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
    public function beneficiary()
    {
        return $this->belongsTo(wb_account_details::class, 'account_id', 'id');
    }
    public function esusuTXN()
    {
        return $this->hasMany(EsusuTXN::class, 'transaction_id', 'id');
    }
    public function TXNBankDetails(){
        return $this->belongsTo(Beneficiary::class,  'id','transaction_id');
    }
}
