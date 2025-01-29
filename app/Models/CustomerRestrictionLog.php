<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerRestrictionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_permission_id',
        'remark',
        'restricted_by_system',
        'restriction_log_type_id',
        'transaction_reference', 'session_id',
        'created_by'
    ];
    public function permission()
    {
        return $this->belongsTo(CustomerPermission::class, "customer_permission_id", 'id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, "user_id", 'id');
    }
    public function type()
    {
        return $this->hasOne(RestrictionLogType::class, "restriction_log_type_id", 'id');
    }
}
