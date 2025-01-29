<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountLienTrack extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'amount',
        'amount_paid',
        'amount_remaining',
        'status_id',
        'lien_reference'
    ];
}
