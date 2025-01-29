<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class user_wallet extends Model
{
    use HasFactory;
    protected $table = 'user_wallets';

    protected $fillable = [
        'balance',
        'branch_id',
        'created_by',
        'modified_by',
        'wb_account_detail_id',
    ];

    public function wb_account_details()
    {
        return $this->belongsTo(wb_account_details::class,'wb_account_detail_id','id');
    }
}
