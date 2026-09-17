<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyFinancialEntry extends Model
{
    use HasFactory;

    protected $table = 'company_financial_entries';

    protected $fillable = [
        'company_id',
        'user_id',
        'target_savings_id',
        'campaign_id',
        'transaction_id',
        'type',
        'category',
        'amount',
        'reference',
        'description',
        'metadata',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Company that owns this financial entry.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Customer associated with this financial entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Target Savings record associated with this entry.
     */
    public function targetSavings(): BelongsTo
    {
        return $this->belongsTo(
            TargetSaving::class,
            'target_savings_id'
        );
    }

    /**
     * Main Finova transaction associated with this entry.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(
            transactions::class,
            'transaction_id'
        );
    }

    public function campaign(): BelongsTo
{
    return $this->belongsTo(
        Campaign::class,
        'campaign_id'
    );
}
}