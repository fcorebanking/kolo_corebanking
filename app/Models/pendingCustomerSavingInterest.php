<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class pendingCustomerSavingInterest extends Model
{
    use HasFactory;
    public function wb_account()
    {
        return $this->belongsTo(wb_account_details::class,'wb_account_id','id');
    }
}
