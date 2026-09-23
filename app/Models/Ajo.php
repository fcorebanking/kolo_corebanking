<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ajo extends Model
{
    use HasFactory;

    protected $table = 'ajos';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_MATURED = 'matured';
    public const STATUS_CANCELLED = 'cancelled';

    public const FEE_SPLIT_COMPANY = 40.00;
    public const FEE_SPLIT_CREATOR = 60.00;

    protected $fillable = [
        'creator_user_id',
        'company_id',
        'creator_ajo_account_id',
        'ajo_name',
        'description',
        'collection_amount',
        'collection_frequency',
        'collection_interval',
        'collection_day',
        'collection_time',
        'start_date',
        'maturity_date',
        'next_collection_at',
        'creation_fee',
        'maturity_fee_percentage',
        'early_leave_penalty_type',
        'early_leave_penalty_value',
        'insufficient_balance_fee_type',
        'insufficient_balance_fee_value',
        'company_fee_share_percentage',
        'creator_fee_share_percentage',
        'max_members',
        'visibility',
        'terms',
        'terms_version',
        'status',
    ];

    protected $casts = [
        'collection_amount' => 'decimal:2',
        'collection_interval' => 'integer',
        'start_date' => 'date',
        'maturity_date' => 'date',
        'next_collection_at' => 'datetime',
        'creation_fee' => 'decimal:2',
        'maturity_fee_percentage' => 'decimal:4',
        'early_leave_penalty_value' => 'decimal:4',
        'insufficient_balance_fee_value' => 'decimal:4',
        'company_fee_share_percentage' => 'decimal:2',
        'creator_fee_share_percentage' => 'decimal:2',
        'max_members' => 'integer',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_user_id', 'id');
    }

    public function company()
    {
        return $this->belongsTo(company::class, 'company_id', 'id');
    }

    public function creatorAjoAccount()
    {
        return $this->belongsTo(AjoAccount::class, 'creator_ajo_account_id', 'id');
    }

    public function members()
    {
        return $this->hasMany(AjoMember::class, 'ajo_id', 'id');
    }

    public function invitations()
    {
        return $this->hasMany(AjoInvitation::class, 'ajo_id', 'id');
    }

    public function collectionCycles()
    {
        return $this->hasMany(AjoCollectionCycle::class, 'ajo_id', 'id');
    }

    public function transactions()
    {
        return $this->hasMany(AjoTransaction::class, 'ajo_id', 'id');
    }

    /**
     * Calculate the split for a shared Ajo fee.
     *
     * The creator/company percentages are snapshotted on the Ajo so
     * the same split remains valid for the lifetime of the Ajo.
     */
    public function splitFee(float $fee): array
    {
        $fee = round(max($fee, 0), 2);

        $companyPercentage = (float) (
            $this->company_fee_share_percentage
            ?? self::FEE_SPLIT_COMPANY
        );

        $creatorPercentage = (float) (
            $this->creator_fee_share_percentage
            ?? self::FEE_SPLIT_CREATOR
        );

        $companyShare = round(
            $fee * ($companyPercentage / 100),
            2
        );

        $creatorShare = round(
            $fee - $companyShare,
            2
        );

        return [
            'total_fee' => $fee,
            'company_percentage' => $companyPercentage,
            'creator_percentage' => $creatorPercentage,
            'company_share' => $companyShare,
            'creator_share' => $creatorShare,
        ];
    }

    /**
     * Text that can be inserted into the Ajo conditions shown to members.
     */
    public function getFeeSplitSummaryAttribute(): string
    {
        $companyPercentage = (float) (
            $this->company_fee_share_percentage
            ?? self::FEE_SPLIT_COMPANY
        );

        $creatorPercentage = (float) (
            $this->creator_fee_share_percentage
            ?? self::FEE_SPLIT_CREATOR
        );

        return "Insufficient-balance fees, maturity fees and early-leave penalties are split "
            . number_format($companyPercentage, 2)
            . "% to the company and "
            . number_format($creatorPercentage, 2)
            . "% to the Ajo creator. The Ajo creation fee belongs 100% to the company.";
    }
}