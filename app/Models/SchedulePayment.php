<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledPayment extends Model
{
    protected $fillable = [
        'user_id',
        'recipient_name',
        'recipient_account',
        'recipient_bank',
        'bank_code',
        'amount',
        'scheduled_date',
        'scheduled_time',
        'transaction_pin',
        'status',
        'attempts',
        'executed_at',
        'failure_reason',
        'token',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
