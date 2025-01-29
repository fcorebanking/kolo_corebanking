<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class loan extends Model
{
    use HasFactory;
    protected $hidden = [
        'id',
        // 'user_id',
        // 'loan_product_id',
        // 'LoanBranch_id',
        // 'LoanOfficer_id',
        // 'created_by',
        // 'modified_by',
    ];
    protected $fillable=['loan_status_id','remark','modified_by','missedLoanLeft','missedLoan','principalLeft','InterestLeft','loanBalance','loan_status_id'];
    public function branch()
    {
        return $this->belongsTo(branch::class,'LoanBranch_id','id');
    }
    public function author()
    {
        return $this->belongsTo(User::class,'created_by','id');
    }
    public function editor()
    {
        return $this->belongsTo(User::class,'modified_by','id');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function LoanOfficer()
    {
        return $this->belongsTo(User::class,'LoanOfficer_id','id');
    }
    public function LoanProduct()
    {
        return $this->belongsTo(loan_product::class);
    }
    public function Guarantor(){
        return $this->hasMany(LoanGuarantor::class, 'loan_id', 'id');
    }
    public function PeriodID(){
        return $this->belongsTo(loan_period::class, 'period_id', 'id');
    }
    public function Paments(){
        return $this->hasMany(LoanRepayment::class)->where('status_id',1);
    }

    public function loanStatus(){
        return $this->belongsTo(loanStatus::class,'loan_status_id', 'statusID');
    }
    public function loanExtension(){
        return $this->hasMany(AddedLoanLog::class);
    }
    public function SumloanExtension(){
        return $this->hasMany(AddedLoanLog::class)->sum('amount');
    }
    public function group()
    {
        return $this->belongsTo(Grouploan::class,'group_id','id');
    }
    public function AddedLoanLog(){
        return $this->hasMany(AddedLoanLog::class);
    }
    //loanStatus
}
