<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountLienLog extends Model
{
    use HasFactory;

    protected $fillable = [
        "wallet_id",
        "amount",
        "amount_paid",
        "amount_remaining",
        "lien_reference",
        "lien_type",
        "status_id",
        "remark"
    ];
}
