<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchValut extends Model
{   
    use HasFactory;
    protected $fillable=['branch_id', 'company_valut_id','created_by', 'transaction_id' ,'value','status'];
    public function branch()
    {
        return $this->belongsTo(branch::class,'branch_id','id');
    }
    public function companyVault()
    {
        return $this->belongsTo(CompanyValut::class,'company_valut_id','id');
    }
    public function transactionID()
    {
        return $this->belongsTo(transactions::class,'transaction_id','id');
    }
}
