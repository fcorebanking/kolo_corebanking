<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AjoMember extends Model
{
    use HasFactory;

    protected $table = 'ajo_members';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_LEAVE_REQUESTED = 'leave_requested';
    public const STATUS_LEFT = 'left';
    public const STATUS_MATURED = 'matured';
    public const STATUS_REMOVED = 'removed';

    protected $fillable = [
        'ajo_id',
        'ajo_account_id',
        'user_id',
        'balance',
        'total_contributed',
        'insufficient_balance_count',
        'total_insufficient_balance_fees',
        'total_maturity_fees',
        'total_early_leave_penalties',
        'status',
        'terms_snapshot',
        'terms_version',
        'terms_accepted_at',
        'joined_at',
        'left_at',
        'early_leave_requested_at',
        'early_leave_processed_at',
        'early_leave_processed_by',
        'early_leave_reason',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_contributed' => 'decimal:2',
        'total_insufficient_balance_fees' => 'decimal:2',
        'total_maturity_fees' => 'decimal:2',
        'total_early_leave_penalties' => 'decimal:2',
        'insufficient_balance_count' => 'integer',
        'terms_accepted_at' => 'datetime',
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'early_leave_requested_at' => 'datetime',
        'early_leave_processed_at' => 'datetime',
    ];

    public function ajo()
    {
        return $this->belongsTo(Ajo::class, 'ajo_id', 'id');
    }

    public function ajoAccount()
    {
        return $this->belongsTo(AjoAccount::class, 'ajo_account_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function earlyLeaveProcessedBy()
    {
        return $this->belongsTo(User::class, 'early_leave_processed_by', 'id');
    }

    public function collectionCycles()
    {
        return $this->hasMany(
            AjoCollectionCycle::class,
            'ajo_member_id',
            'id'
        );
    }

    public function transactions()
    {
        return $this->hasMany(
            AjoTransaction::class,
            'ajo_member_id',
            'id'
        );
    }
}