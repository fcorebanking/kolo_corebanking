<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class branch extends Model
{
    use HasFactory;
    protected $table = 'branch';

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
    public function currency()
    {
        return $this->belongsTo(currencies::class, 'currency_id', 'id');
    }
    public function status()
    {
        return $this->belongsTo(status::class, 'status_id', 'id');
    }
    public function editor()
    {
        return $this->belongsTo(User::class, 'modified_by', 'id');
    }
    public function author()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
    public function wallet()
    {
        return $this->hasOne(BranchWallet::class);
    }

    public function currentcy()
    {
        return $this->hasOne(currencies::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'branch_manager', 'id');
    }
}
