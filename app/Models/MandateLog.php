<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MandateLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'mandate_id',
        'payment_reference',
        'marchant_ref',
        'response_description',
        'amount',
        'narration',
        'charge',
        'payment_status',
        'payment_date'
    ];
}
