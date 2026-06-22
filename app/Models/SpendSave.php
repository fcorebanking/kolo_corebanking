<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpendSave extends Model
{
    protected $fillable = [
        "user_id",
        "company_id",
        "spend_save_charge_or_percentage",
        "balance",
        "status_id",

    ];
}
