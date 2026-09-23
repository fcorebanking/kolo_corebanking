<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AjoAccount extends Model
{
    use HasFactory;

    protected $table = 'ajo_accounts';

    protected $fillable = [
        'user_id',
        'company_id',
        'balance',
        'status',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function company()
    {
        return $this->belongsTo(company::class, 'company_id', 'id');
    }

    public function createdAjos()
    {
        return $this->hasMany(Ajo::class, 'creator_ajo_account_id', 'id');
    }

    public function memberships()
    {
        return $this->hasMany(AjoMember::class, 'ajo_account_id', 'id');
    }

    public function sourceTransactions()
    {
        return $this->hasMany(AjoTransaction::class, 'source_ajo_account_id', 'id');
    }

    public function destinationTransactions()
    {
        return $this->hasMany(AjoTransaction::class, 'destination_ajo_account_id', 'id');
    }

    /**
     * The amount sitting in this personal Ajo Account itself is the
     * amount available for normal Ajo-account withdrawal, subject to
     * the service checking that it is greater than zero.
     *
     * Funds already moved into an AjoMember position are not part of
     * this balance and therefore cannot be withdrawn from this account.
     */
    public function getAvailableBalanceAttribute(): float
    {
        return max((float) $this->balance, 0);
    }
}