<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountMandate extends Model
{
    use HasFactory;

    protected $fillable = [
        "wallet_id",
        "amount",
        "amount_paid",
        "session_id",
        "amount_remaining",
        "account_name",
        "account_number",
        "status_id",
        "mandate_reference_number"
    ];  
}
