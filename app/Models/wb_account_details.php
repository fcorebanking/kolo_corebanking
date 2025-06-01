<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class wb_account_details extends Model
{
    use HasFactory;
    protected $table    = 'wb_account_details';
    protected $fillable = [
        "user_id",
        "account_product_id",
        "bankid",
        "virtual_accnum",
        "acc_reference",
        "has_atm_card",
        "currency_id",
        "next_maintenance_date",
    ];

    public function user_wallet()
    {
        return $this->belongsTo(user_wallet::class, 'id', 'wb_account_detail_id');
    }
    public function currency()
    {
        return $this->belongsTo(currencies::class, 'currency_id', 'id');
    }
    public function customerBalance()
    {
        return $this->belongsTo(user_wallet::class);
    }
    public function bank()
    {
        return $this->hasOne(banks::class, 'id', 'bankid');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function accountType()
    {
        return $this->belongsTo(savings_product::class, 'account_product_id', 'id');
    }
    public function investment()
    {
        return $this->hasMany(Investmentbooking::class, 'wb_accountdetails_id', 'id')->where('status', 1);
    }

}
