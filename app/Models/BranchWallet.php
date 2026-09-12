<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchWallet extends Model
{
    use HasFactory;
    protected $table = 'branch_wallet';
 
    protected $fillable = [
        'balance',
        'branch_id',
        'created_by',
        'modified_by',
        'accountno',
    ];

    public function editor()
    {
        return $this->belongsTo(User::class, 'modified_by', 'id');
    }
    public function author()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
    public function branch()
    {
        return $this->belongsTo(User::class, 'branch_id', 'id');
    }
}
