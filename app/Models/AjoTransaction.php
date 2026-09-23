<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AjoTransaction extends Model
{
    use HasFactory;

    protected $table = 'ajo_transactions';

    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_WITHDRAWAL = 'withdrawal';
    public const TYPE_CREATION_FEE = 'creation_fee';
    public const TYPE_SCHEDULED_COLLECTION = 'scheduled_collection';
    public const TYPE_INSUFFICIENT_BALANCE_FEE = 'insufficient_balance_fee';
    public const TYPE_MATURITY_FEE = 'maturity_fee';
    public const TYPE_MATURITY_RETURN = 'maturity_return';
    public const TYPE_EARLY_LEAVE_PENALTY = 'early_leave_penalty';
    public const TYPE_EARLY_LEAVE_RETURN = 'early_leave_return';
    public const TYPE_REVERSAL = 'reversal';

    protected $fillable = [
        'ajo_id',
        'ajo_member_id',
        'ajo_collection_cycle_id',
        'user_id',
        'company_id',
        'source_ajo_account_id',
        'destination_ajo_account_id',
        'main_account_id',
        'finova_transaction_id',
        'company_financial_entry_id',
        'type',
        'amount',
        'fee',
        'company_fee_share',
        'creator_fee_share',
        'source_balance_before',
        'source_balance_after',
        'destination_balance_before',
        'destination_balance_after',
        'transaction_reference',
        'status',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'company_fee_share' => 'decimal:2',
        'creator_fee_share' => 'decimal:2',
        'source_balance_before' => 'decimal:2',
        'source_balance_after' => 'decimal:2',
        'destination_balance_before' => 'decimal:2',
        'destination_balance_after' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function ajo()
    {
        return $this->belongsTo(Ajo::class, 'ajo_id', 'id');
    }

    public function member()
    {
        return $this->belongsTo(
            AjoMember::class,
            'ajo_member_id',
            'id'
        );
    }

    public function collectionCycle()
    {
        return $this->belongsTo(
            AjoCollectionCycle::class,
            'ajo_collection_cycle_id',
            'id'
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function company()
    {
        return $this->belongsTo(company::class, 'company_id', 'id');
    }

    public function sourceAjoAccount()
    {
        return $this->belongsTo(
            AjoAccount::class,
            'source_ajo_account_id',
            'id'
        );
    }

    public function destinationAjoAccount()
    {
        return $this->belongsTo(
            AjoAccount::class,
            'destination_ajo_account_id',
            'id'
        );
    }

    public function mainAccount()
    {
        return $this->belongsTo(
            wb_account_details::class,
            'main_account_id',
            'id'
        );
    }

    public function finovaTransaction()
    {
        return $this->belongsTo(
            transactions::class,
            'finova_transaction_id',
            'id'
        );
    }

    public function companyFinancialEntry()
    {
        return $this->belongsTo(
            CompanyFinancialEntry::class,
            'company_financial_entry_id',
            'id'
        );
    }
}