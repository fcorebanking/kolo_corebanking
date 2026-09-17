<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;

    protected $table = 'campaigns';

    protected $fillable = [
        'user_id',
        'company_id',
        'creator_account_id',
        'creator_account_number',
        'campaign_name',
        'description',
        'target_amount',
        'total_contributed',
        'withdrawable_amount',
        'campaign_fee_percentage',
        'status',
        'closed_at',
        'last_withdrawn_at',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'total_contributed' => 'decimal:2',
        'withdrawable_amount' => 'decimal:2',
        'campaign_fee_percentage' => 'decimal:4',
        'closed_at' => 'datetime',
        'last_withdrawn_at' => 'datetime',
    ];

    /**
     * Campaign creator.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    /**
     * Company behind the Campaign.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(
            Company::class,
            'company_id'
        );
    }

    /**
     * Internal creator account.
     *
     * No database foreign key is required here because the
     * account table/model in the existing Finova application
     * uses the wb_account_details model.
     */
    public function creatorAccount(): BelongsTo
    {
        return $this->belongsTo(
            wb_account_details::class,
            'creator_account_id',
            'id'
        );
    }

    /**
     * Campaign transaction history.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(
            CampaignTransaction::class,
            'campaign_id'
        );
    }
}