<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffWallet extends Model
{
    use HasFactory;
    protected $table = 'staff_wallet';

    protected $fillable = [
        'accountno',
        'balance',
        'user_id',
        'created_by',
        'modified_by','commission'
    ];

    public function editor()
    {
        return $this->belongsTo(User::class, 'modified_by', 'id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
