<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GtbWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_reference',
        'virtual_account_number',
        'principal_amount',
        'settled_amount',
        'fee_charged',
        'transaction_date',
        'customer_identifier',
        'transaction_indicator',
        'remarks',
        'currency',
        'channel',
        'encrypted_body',
    ];
}
