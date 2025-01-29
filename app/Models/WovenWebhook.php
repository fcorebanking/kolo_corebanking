<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WovenWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        "nuban",
        "bank_code",
        "account_name",
        "account_email",
        "account_mobile_number",
        "is_test",
        "account_reference",
        "source_bank_code",
        "source_nuban",
        "source_account_name",
        "source_bvn",
        "amount",
        "fee",
        "currency",
        "transaction_time",
        "nip_session_id",
        "unique_reference",
        "transaction_type",
        "status",
        "settled_status",
        "merchantId",
        "vnubanId",
        "requestId",
        "amount_payable",
        "hash"
    ];
}
