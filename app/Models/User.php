<?php
namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;
    // ,TwoFactorAuthenticatable;
    use HasRoles;
    /**
     * The attributes that are mass assignable.
     *

     * @var string[]
     *
     *
     */
    protected $guarded  = [];
    protected $fillable = [
        'uuid',
        'email',
        'company_id',
        'first_name',
        'last_name',
        'phone',
        'profile_pic',
        'username',
        'sms',
        'whatsapp',
        'pin',
        'bank_id',
        'reg_date',
        'status_id',
        'bvn',
        'bvn_verified_at',
        'branch_id',
        'bvn_status',
        'nin',
        'payroll_setup',
        'signature',
        'session_user',
        'address',
        'gender',
        'virtual_account',
        'account_reference',
        'uuid',
        'otp',
        'otp_expiration_date',
        'phone_verification_status_id',
        'branch',
        "kyc_level_id",
        'role_id',
        'created_by',
        'email_verified_at',
        'password',
        'validID_file',
        'otherID_file',
        'type',
        'dob',
        'account_officer_id',
        'verification_hash',
        'sex',
        'utilityBillID_file',
        'user_type_id',
        'old_wba_user',
        'whatsapp_status_id',
        'ussd_status_id',
        'fcm_token',
        'agent_id',
        'is_agent',
        'login_sms_otp_status',
        'google2fa_secret',
        'remittar_customer_id',
        'remittar_bankCode',
        'remittar_accountNo',
        'referral_id',

        'nike_name',
        'occupation',
        'marital_status',
        'alternative_phone',
        'business_name',
        'home_address',
        'state_of_origin',
        'country_id', 'settlement_wallet_id',
        'transfer_token',
        'transfer_schedule_token',
        'access_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'id',
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    /**
     * Interact with the user's first name.
     *
     * @param  string  $value
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */

    public function wb_account_details()
    {
        return $this->hasMany(wb_account_details::class);
    }
    public function staff_wallet()
    {
        return $this->belongsTo(StaffWallet::class, 'id', 'user_id');
    }
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function branch()
    {
        return $this->belongsTo(branch::class);
    }

    public function wallet()
    {
        return $this->hasOne(StaffWallet::class, "user_id", 'id');
    }

    public function terminals()
    {
        return $this->hasMany(AgentTerminal::class, "user_id", 'id');
    }

    public function cooperateAccount()
    {
        return $this->hasOne(CooperateCustomer::class, "user_id", 'id');
    }

    public function kycLevel()
    {
        return $this->hasOne(KycLevel::class, "id", 'kyc_level_id');
    }

    public function userType()
    {
        return $this->belongsTo(UserType::class, 'user_type_id', 'id');
    }

    public function card()
    {
        return $this->hasOne(UserCard::class);
    }

    public function apiKey()
    {
        return $this->hasOne(CorperateUserApiKey::class, "user_id", 'id');
    }

    public function tier()
    {
        return $this->belongsTo(KycLevel::class, "kyc_level_id", 'id');
    }
    public function Restriction()
    {
        return $this->hasMany(CustomerRestriction::class, "user_id", 'id');
    }

    public function accounts()
    {
        return $this->hasMany(wb_account_details::class, 'user_id', 'id');
    }

    public function status()
    {
        return $this->belongsTo(status::class, 'status_id', 'id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function myOfficer()
    {
        return $this->belongsTo(User::class, 'account_officer_id', 'id');
    }
    public function clients()
    {
        return $this->hasMany(User::class, 'account_officer_id', 'id');
    }
    public function staffpermission()
    {
        return $this->belongsTo(StaffpermissionTable::class, 'id', 'user_id');
    }

    public function staff_can()
    {
    }

    public function nok_details()
    {
        # code...

        return $this->hasOne('App\Models\NextOfKinDetail', 'user_id');
    }

    public function customerPermissions()
    {
        return $this->belongsToMany(CustomerPermission::class, 'main_customer_restrictions', 'user_id', 'customer_permission_id')->withPivot(['remark']);
    }

    public function bank()
    {
        return $this->hasOne('App\Models\banks', 'id', 'bank_id');
    }
    public function loan()
    {
        return $this->hasMany(loan::class)->where('loan_status_id', 1);
    }
    public function allLoan()
    {
        return $this->hasMany(loan::class);
    }
    public function UserLoanGroup()
    {
        return $this->belongsTo(UserGroupLoan::class);
    }
    public function ReferralUpline()
    {
        return $this->belongsTo(User::class, 'referral_id', 'id');
    }
    protected function google2faSecret(): Attribute
    {
        $getter = function ($value) {
            return (string) $value;
        };

        $setter = function ($value) {
            return (string) $value;
        };

        return new Attribute($getter, $setter);
    }
    public function staff_settlement_wallet()
    {
        return $this->belongsTo(user_wallet::class, 'settlement_wallet_id', 'id');
    }
public function targetSavings(): HasMany
{
    return $this->hasMany(TargetSaving::class, 'user_id', 'id');
}

public function targetSavingsSchedules(): HasMany
{
    return $this->hasMany(TargetSavingsSchedule::class, 'user_id', 'id');
}
}
