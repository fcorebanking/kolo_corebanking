<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApploanSettings extends Model
{
    use HasFactory;
    protected $table ='apploan_settings';
    public function LoanProduct()
    {
        return $this->belongsTo(loan_product::class);
    }
    public function PeriodID(){
        return $this->belongsTo(loan_period::class, 'repayment', 'id');
    }
}
