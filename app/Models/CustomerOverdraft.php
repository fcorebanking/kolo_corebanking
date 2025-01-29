<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerOverdraft extends Model
{
    use HasFactory;
    protected $fillable = [
        "user_id",
        "frequency_id",
        "interest",
        "transaction_id",
        "amount"
    ];
}
