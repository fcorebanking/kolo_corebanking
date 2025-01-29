<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetMatrixWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        "amount",
        "account_name",
        "narration",
        "session_id",
        "account_number",
        "source_account_number",
        "source_account_name"
    ];
}
