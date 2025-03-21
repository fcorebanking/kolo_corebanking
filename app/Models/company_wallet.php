<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class company_wallet extends Model
{
    use HasFactory;
    protected $fillable = [
        'balance',
        'sms',
        'ussd',
        'company_id',
        'bank_id',
        'whatsapp',
        'currency_code',
        'card_balance',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');

    }

    public function bank()
    {
        return $this->hasOne(banks::class, "id", 'bank_id');
    }
}
