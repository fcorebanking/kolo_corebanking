<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanRepayment extends Model
{
    use HasFactory;
    protected $fillable=['initiator_id','status_id','P','I'];

    public function author()
    {
        return $this->belongsTo(User::class,'created_by','id');
    }
    public function loan()
    {
        return $this->belongsTo(loan::class,'loan_id','id');
    }
}
