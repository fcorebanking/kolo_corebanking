<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanGuarantor extends Model
{
    use HasFactory;
    public function GuarantorDetails(){
        return $this->belongsTo(guarantor::class, 'guarantor_id', 'id');
    }
}
