<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AjoInvitation extends Model
{
    use HasFactory;

    protected $table = 'ajo_invitations';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'ajo_id',
        'invited_by',
        'invited_user_id',
        'status',
        'terms_snapshot',
        'terms_version',
        'sent_at',
        'responded_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function ajo()
    {
        return $this->belongsTo(Ajo::class, 'ajo_id', 'id');
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by', 'id');
    }

    public function invitee()
    {
        return $this->belongsTo(User::class, 'invited_user_id', 'id');
    }
}