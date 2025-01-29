<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingSaving extends Model
{
    use HasFactory;

    protected $fillable = ['uuid','w_charge','amount','company_id','desc','account_type','pending','status_id','created_by','transaction_type_id','teller_option_id','transaction_id','initiator'];
    public function user()
    {
        return $this->belongsTo(User::class,'uuid','uuid');
    }
    public function teller()
    {
        return $this->belongsTo(User::class,'created_by','id');
    }
    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary::class);
    }
}
