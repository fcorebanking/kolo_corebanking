<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AjoCollectionCycle extends Model
{
    use HasFactory;

    protected $table = 'ajo_collection_cycles';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SUCCESSFUL = 'successful';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'ajo_id',
        'ajo_member_id',
        'cycle_number',
        'scheduled_for',
        'processed_at',
        'amount',
        'source_balance_before',
        'source_balance_after',
        'member_balance_before',
        'member_balance_after',
        'insufficient_balance',
        'insufficient_balance_count',
        'fee_amount',
        'company_fee_share',
        'creator_fee_share',
        'status',
        'failure_reason',
        'transaction_reference',
        'metadata',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'processed_at' => 'datetime',
        'amount' => 'decimal:2',
        'source_balance_before' => 'decimal:2',
        'source_balance_after' => 'decimal:2',
        'member_balance_before' => 'decimal:2',
        'member_balance_after' => 'decimal:2',
        'insufficient_balance' => 'boolean',
        'insufficient_balance_count' => 'integer',
        'fee_amount' => 'decimal:2',
        'company_fee_share' => 'decimal:2',
        'creator_fee_share' => 'decimal:2',
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

    public function transactions()
    {
        return $this->hasMany(
            AjoTransaction::class,
            'ajo_collection_cycle_id',
            'id'
        );
    }
}