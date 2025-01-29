<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class banks extends Model
{
    use HasFactory;

    protected $fillable = [
        "bank_name",
        "bank_code",
        "created_at",
        "updated_at"
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wbAccount()
    {
        return $this->belongsTo(wb_account_details::class);
    }
}
