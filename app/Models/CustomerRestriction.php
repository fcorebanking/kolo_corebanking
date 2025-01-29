<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerRestriction extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'customerpermission_id',
        'remark',
        'created_by',
    ];
    public function permission()
    {
        return $this->belongsTo(CustomerPermission::class, "customerpermission_id", 'id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, "user_id", 'id');
    }
}
