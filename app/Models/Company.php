<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;
    protected $table = 'company';

    public function user()
    {
        return $this->hasMany(User::class);
    }
    public function branch()
    {
        $sucription_suspended_status_id = 14;
        return $this->hasMany(branch::class, 'company_id', 'id')->where('status_id', '!=', $sucription_suspended_status_id);
    }
    public function allStaff()
    {
        $sucription_suspended_status_id = 14;
        return $this->hasMany(User::class)->where('user_type_id', 2)->where('status_id', '!=', $sucription_suspended_status_id);
    }
    public function allCustomer()
    {
        $sucription_suspended_status_id = 14;
        return $this->hasMany(User::class)->where('user_type_id', 1)->where('status_id', '!=', $sucription_suspended_status_id);
    }
    public function wallet()
    {
        return $this->hasOne(company_wallet::class, 'company_id', 'id');
    }
    public function allWallet()
    {
        return $this->hasMany(company_wallet::class, 'company_id', 'id');
    }
    public function apiKey()
    {
        return $this->hasOne(ApiKey::class, 'company_id', 'id');
    }
    public function card()
    {
        return $this->hasOne(company_wallet::class, 'company_id', 'id');
    }
    public function country()
    {
        return $this->belongsTo(Contries::class, 'countries_id', 'id');
    }
    public function createdby()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
    public function currency()
    {
        return $this->belongsTo(currencies::class);
    }

    public function verificationStatus()
    {
        return $this->belongsTo(VerificationStatus::class, 'verification_status_id', 'id');
    }

    public function status()
    {
        return $this->belongsTo(status::class, 'status_id', 'id');
    }

    public function sub()
    {
        return $this->hasOne(sub::class, 'company_id', 'id');
    }
    public function setting()
    {
        return $this->hasOne(company_settings::class, 'company_id', 'id');
    }
    public function savingsProducts()
    {
        return $this->hasMany(savings_product::class, 'company_id', 'id');
    }
    public function CompanySettlement()
    {
        return $this->hasOne(CompanySettlement::class, 'company_id', 'id');
    }
    public function Transactionsettings()
    {
        return $this->hasOne(TransactionSettings::class, 'company_id', 'id');
    }
    //
    public function companyEmail()
    {
        return $this->hasOne(CompanyEmail::class, 'company_id', 'id');
    }
    public function CustomerReferalSetting()
    {
        return $this->hasOne(CustomerReferalSetting::class, 'company_id', 'id');
    }
    public function companyClosingBalance()
    {
        return $this->hasMany(ClosingBalance::class, 'company_id', 'id');
    }
}
