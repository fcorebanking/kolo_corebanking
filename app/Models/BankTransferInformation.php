<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankTransferInformation extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'account_number',
        'bank_code',
        'amount',
        'transfer_entity_type',
        'type',
        'customer_charge',
        'company_charge',
        'description',
        'account_name',
        'created_by'
    ];
}
