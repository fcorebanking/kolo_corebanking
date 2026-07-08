<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerSalaryTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'customer_salary_id',
        'transaction_id',
        'status',
        'attempts',
        'executed_at',
        'failure_reason',

    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
