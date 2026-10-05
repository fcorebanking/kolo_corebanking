<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TargetSaving extends Model
{
    use HasFactory;

    protected $table = 'target_savings';

    protected $fillable = [
        'user_id',
        'company_id',
        'source_account_id',
        'destination_account_id',
        'target_name',
        'target_amount',
        'current_balance',
        'total_deposited',
        'total_interest_earned',
        'interest_rate',
        'early_withdrawal_fee_percentage',
        'withdrawal_date',
        'last_interest_date',
        'matured_at',
        'withdrawn_at',
        'status',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'total_deposited' => 'decimal:2',
        'total_interest_earned' => 'decimal:2',
        'interest_rate' => 'decimal:4',
        'early_withdrawal_fee_percentage' => 'decimal:4',
        'withdrawal_date' => 'date',
        'last_interest_date' => 'date',
        'matured_at' => 'datetime',
        'withdrawn_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(
            TargetSavingsTransaction::class,
            'target_savings_id',
            'id'
        );
    }

    public function schedule(): HasOne
    {
        return $this->hasOne(
            TargetSavingsSchedule::class,
            'target_savings_id',
            'id'
        );
    }
}
