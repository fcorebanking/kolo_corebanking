<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_type',
        'amount',
        'company_id',
        'account_number',
        'status_id',
        'charge',
        'transaction_date',
        'description',
        'body',
        'unique_reference'
    ];


    public function status()
    {
        return $this->belongsTo(status::class, 'status_id', 'id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
