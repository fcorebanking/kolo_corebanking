<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mandate extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'payer_name',
        'pay_address',
        'email_address',
        'phone_number',
        'bank_id',
        'account_number',
        'account_name',
        'narration',
        'variable_amounts',
        'start_date',
        'end_date',
        'first_payment_date',
        'subscriber_code',
        'repeat',
        'frequency',
        'amount',
        'mandate_code',
        'status_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
