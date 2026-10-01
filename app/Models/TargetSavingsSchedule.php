<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TargetSavingsSchedule extends Model
{
    use HasFactory;

    protected $table = 'target_savings_schedules';

    protected $fillable = [
        'target_saving_id',
        'user_id',
        'source_account_id',
        'amount',
        'frequency',
        'first_deposit_at',
        'next_deposit_at',
        'last_deposit_at',
        'last_attempt_at',
        'notification_minutes_before',
        'notification_sent_at',
        'last_deposit_status',
        'last_deposit_error',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'first_deposit_at' => 'datetime',
        'next_deposit_at' => 'datetime',
        'last_deposit_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'notification_minutes_before' => 'integer',
        'notification_sent_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function targetSaving(): BelongsTo
    {
        return $this->belongsTo(TargetSaving::class, 'target_saving_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function sourceAccount(): BelongsTo
    {
        return $this->belongsTo(wb_account_details::class, 'source_account_id', 'id');
    }
}
