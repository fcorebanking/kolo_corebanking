<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class payLoanPenalty extends Model
{
    use HasFactory;
    protected $table ='pay_loan_penalty';
    public function Loan()
    {
        return $this->belongsTo(Loan::class);
    }
}
