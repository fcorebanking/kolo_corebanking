<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Investmentbooking extends Model
{
    use HasFactory;
    protected $table = 'investmentbooking';
    protected $fillable = [
        'wb_accountdetails_id',
        'investment_product_id',
        'amount',
        'wallet',
        'balance',
        'expected_balance',
        'duration',
        'start_date',
        'end_date',
        'status','paymentOption'
    ];
    public function status()
    {
        return $this->belongsTo(company_status::class,'status_id','id');
    }
    public function plan()
    {
        return $this->belongsTo(InvestmentProduct::class,'investment_product_id','id');
    }

    public function user_wallet()
    {
        return $this->belongsTo(user_wallet::class,'wb_account_detail_id','wb_accountdetails_id');
    }
    public function wb_account_details()
    {
        return $this->belongsTo(wb_account_details::class,'wb_accountdetails_id','id');
    }//wb_account_details
}
