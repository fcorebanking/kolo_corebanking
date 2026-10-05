<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedSavingsTransaction extends Model
{
    use HasFactory;

    protected $table = 'fixed_savings_transactions';

    protected $fillable = [
        'fixed_saving_id',
        'user_id',
        'company_id',
        'type',
        'amount',
        'fee',
        'balance_before',
        'balance_after',
        'interest_date',
        'transaction_reference',
        'status',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'interest_date' => 'date',
        'metadata' => 'array',
    ];

    public function fixedSavings(): BelongsTo
    {
        return $this->belongsTo(FixedSaving::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}