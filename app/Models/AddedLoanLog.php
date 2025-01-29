<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AddedLoanLog extends Model
{
    use HasFactory;
    public function loan()
    {
        return $this->belongsTo(loan::class,'loan_id','id');
    }
}
